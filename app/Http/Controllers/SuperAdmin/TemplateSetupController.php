<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Country;
use App\Models\QcQuestion;
use App\Models\QcSection;
use App\Models\QcTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TemplateSetupController extends Controller
{
    private const QUESTION_TYPES = [
        QcQuestion::QUESTION_TYPE_POINTS,
        QcQuestion::QUESTION_TYPE_YES_NO,
        QcQuestion::QUESTION_TYPE_MULTI_SELECT,
        QcQuestion::QUESTION_TYPE_SCORE,
        QcQuestion::QUESTION_TYPE_COMMENT,
    ];

    public function index(): Response
    {
        $templates = QcTemplate::query()
            ->with(['countries:id,name,name2', 'brands:id,name,name2', 'sections.questions'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (QcTemplate $template) => $this->formatTemplate($template));

        return Inertia::render('SuperAdmin/TemplateSetup/Index', [
            'templates' => $templates,
            'countries' => Country::query()->orderBy('name')->get(['id', 'name', 'name2'])->map(fn (Country $country) => [
                'id' => $country->id,
                'name' => $country->name,
                'name_ar' => $country->name2,
                'localized_name' => $country->localized_name,
            ]),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name', 'name2'])->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'name_ar' => $brand->name2,
                'localized_name' => $brand->localized_name,
            ]),
            'questionTypes' => self::QUESTION_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTemplatePayload($request);

        DB::transaction(function () use ($validated) {
            $template = QcTemplate::create($this->templateAttributes($validated));
            $this->syncTemplateRelations($template, $validated);
            $this->syncSections($template, $validated['sections'] ?? []);
        });

        return redirect()
            ->route('super-admin.template-setup.index')
            ->with('success', 'Template created successfully.');
    }

    public function update(Request $request, QcTemplate $template): RedirectResponse
    {
        $validated = $this->validateTemplatePayload($request, $template);

        DB::transaction(function () use ($template, $validated) {
            $template->update($this->templateAttributes($validated));
            $this->syncTemplateRelations($template, $validated);
            $this->syncSections($template, $validated['sections'] ?? []);
        });

        return redirect()
            ->route('super-admin.template-setup.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(QcTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()
            ->route('super-admin.template-setup.index')
            ->with('success', 'Template deleted successfully.');
    }

    public function storeSection(Request $request, QcTemplate $template)
    {
        $validated = $request->validate($this->sectionRules(includeQuestions: false));
        $section = $template->sections()->create($this->sectionAttributes($validated));

        return response()->json($this->formatSection($section->load('questions')), 201);
    }

    public function updateSection(Request $request, QcTemplate $template, QcSection $section)
    {
        abort_unless((int) $section->template_id === (int) $template->id, 404);
        $validated = $request->validate($this->sectionRules(includeQuestions: false));
        $section->update($this->sectionAttributes($validated));

        return response()->json($this->formatSection($section->load('questions')));
    }

    public function destroySection(QcTemplate $template, QcSection $section)
    {
        abort_unless((int) $section->template_id === (int) $template->id, 404);
        $section->delete();

        return response()->json(['deleted' => true]);
    }

    public function storeQuestion(Request $request, QcSection $section)
    {
        $validated = $request->validate($this->questionRules());
        $question = $section->questions()->create($this->questionAttributes($validated));

        return response()->json($this->formatQuestion($question), 201);
    }

    public function updateQuestion(Request $request, QcSection $section, QcQuestion $question)
    {
        abort_unless((int) $question->section_id === (int) $section->id, 404);
        $validated = $request->validate($this->questionRules());
        $question->update($this->questionAttributes($validated));

        return response()->json($this->formatQuestion($question));
    }

    public function destroyQuestion(QcSection $section, QcQuestion $question)
    {
        abort_unless((int) $question->section_id === (int) $section->id, 404);
        $question->delete();

        return response()->json(['deleted' => true]);
    }

    private function validateTemplatePayload(Request $request, ?QcTemplate $template = null): array
    {
        $rules = [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'country_ids' => ['required', 'array', 'min:1'],
            'country_ids.*' => ['integer', 'exists:sma_countries,id'],
            'brand_ids' => ['required', 'array', 'min:1'],
            'brand_ids.*' => ['integer', 'exists:sma_brands,id'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['nullable', 'integer'],
        ];

        foreach ($this->sectionRules('sections.*.', true) as $key => $rule) {
            $rules[$key] = $rule;
        }

        foreach ($this->questionRules('sections.*.questions.*.') as $key => $rule) {
            $rules[$key] = $rule;
        }

        $validated = $request->validate($rules);

        $this->validateNestedOptions($validated);

        return $validated;
    }

    private function sectionRules(string $prefix = '', bool $includeQuestions = true): array
    {
        $rules = [
            $prefix . 'name' => ['required', 'string', 'max:255'],
            $prefix . 'name_ar' => ['required', 'string', 'max:255'],
            $prefix . 'sort_order' => ['required', 'integer', 'min:1'],
        ];

        if ($includeQuestions) {
            $rules[$prefix . 'questions'] = ['required', 'array', 'min:1'];
            $rules[$prefix . 'questions.*.id'] = ['nullable', 'integer'];
        }

        return $rules;
    }

    private function questionRules(string $prefix = ''): array
    {
        return [
            $prefix . 'name' => ['required', 'string', 'max:500'],
            $prefix . 'name_ar' => ['required', 'string', 'max:500'],
            $prefix . 'question_type' => ['required', Rule::in(self::QUESTION_TYPES)],
            $prefix . 'options' => ['nullable', 'array'],
            $prefix . 'options.*' => ['nullable', 'string', 'max:255'],
            $prefix . 'score_value' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            $prefix . 'allow_manual_score' => ['nullable', 'boolean'],
            $prefix . 'sort_order' => ['required', 'integer', 'min:1'],
            $prefix . 'is_required' => ['required', 'boolean'],
        ];
    }

    private function validateNestedOptions(array $validated): void
    {
        foreach ($validated['sections'] ?? [] as $sectionIndex => $section) {
            foreach ($section['questions'] ?? [] as $questionIndex => $question) {
                $options = array_values(array_filter($question['options'] ?? [], fn ($option) => trim((string) $option) !== ''));

                if (($question['question_type'] ?? null) === QcQuestion::QUESTION_TYPE_MULTI_SELECT && count($options) === 0) {
                    validator([], [])->after(function ($validator) use ($sectionIndex, $questionIndex) {
                        $validator->errors()->add("sections.$sectionIndex.questions.$questionIndex.options", 'Multi-select questions require at least one option.');
                    })->validate();
                }
            }
        }
    }

    private function templateAttributes(array $validated): array
    {
        return [
            'brand_id' => Arr::first($validated['brand_ids']),
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'is_active' => (bool) $validated['is_active'],
            'answer_type' => 'Points',
        ];
    }

    private function syncTemplateRelations(QcTemplate $template, array $validated): void
    {
        $template->countries()->sync($validated['country_ids']);
        $template->brands()->sync($validated['brand_ids']);
    }

    private function syncSections(QcTemplate $template, array $sections): void
    {
        $keptSectionIds = [];

        foreach ($sections as $index => $sectionData) {
            $sectionData['sort_order'] = $sectionData['sort_order'] ?? ($index + 1);
            $section = isset($sectionData['id'])
                ? $template->sections()->whereKey($sectionData['id'])->first()
                : null;

            if ($section) {
                $section->update($this->sectionAttributes($sectionData));
            } else {
                $section = $template->sections()->create($this->sectionAttributes($sectionData));
            }

            $keptSectionIds[] = $section->id;
            $this->syncQuestions($section, $sectionData['questions'] ?? []);
        }

        $template->sections()->whereNotIn('id', $keptSectionIds)->delete();
    }

    private function syncQuestions(QcSection $section, array $questions): void
    {
        $keptQuestionIds = [];

        foreach ($questions as $index => $questionData) {
            $questionData['sort_order'] = $questionData['sort_order'] ?? ($index + 1);
            $question = isset($questionData['id'])
                ? $section->questions()->whereKey($questionData['id'])->first()
                : null;

            if ($question) {
                $question->update($this->questionAttributes($questionData));
            } else {
                $question = $section->questions()->create($this->questionAttributes($questionData));
            }

            $keptQuestionIds[] = $question->id;
        }

        $section->questions()->whereNotIn('id', $keptQuestionIds)->delete();
    }

    private function sectionAttributes(array $section): array
    {
        return Arr::only($section, ['name', 'name_ar', 'sort_order']);
    }

    private function questionAttributes(array $question): array
    {
        $type = $question['question_type'];
        $options = array_values(array_filter($question['options'] ?? [], fn ($option) => trim((string) $option) !== ''));

        return [
            'q_type' => in_array($type, [QcQuestion::QUESTION_TYPE_MULTI_SELECT, QcQuestion::QUESTION_TYPE_COMMENT], true)
                ? QcQuestion::TYPE_TEXT
                : QcQuestion::TYPE_POINT_BASED,
            'question_type' => $type,
            'name' => $question['name'],
            'name_ar' => $question['name_ar'],
            'options' => $type === QcQuestion::QUESTION_TYPE_MULTI_SELECT ? $options : null,
            'score_value' => in_array($type, [QcQuestion::QUESTION_TYPE_YES_NO, QcQuestion::QUESTION_TYPE_SCORE], true)
                ? ($question['score_value'] ?? 1)
                : null,
            'allow_manual_score' => $type === QcQuestion::QUESTION_TYPE_SCORE && (bool) ($question['allow_manual_score'] ?? false),
            'sort_order' => $question['sort_order'],
            'is_required' => (bool) $question['is_required'],
        ];
    }

    private function formatTemplate(QcTemplate $template): array
    {
        $template->append('localized_name');

        return [
            'id' => $template->id,
            'name_en' => $template->name_en,
            'name_ar' => $template->name_ar,
            'localized_name' => $template->localized_name,
            'answer_type' => $template->answer_type,
            'is_active' => $template->is_active,
            'country_ids' => $template->countries->pluck('id')->values(),
            'brand_ids' => $template->brands->pluck('id')->values(),
            'countries' => $template->countries->map(fn (Country $country) => [
                'id' => $country->id,
                'localized_name' => $country->localized_name,
            ])->values(),
            'brands' => $template->brands->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'localized_name' => $brand->localized_name,
            ])->values(),
            'sections' => $template->sections->map(fn (QcSection $section) => $this->formatSection($section))->values(),
        ];
    }

    private function formatSection(QcSection $section): array
    {
        return [
            'id' => $section->id,
            'name' => $section->name,
            'name_ar' => $section->name_ar,
            'localized_name' => $section->localized_name,
            'sort_order' => $section->sort_order,
            'questions' => $section->questions->map(fn (QcQuestion $question) => $this->formatQuestion($question))->values(),
        ];
    }

    private function formatQuestion(QcQuestion $question): array
    {
        return [
            'id' => $question->id,
            'name' => $question->name,
            'name_ar' => $question->name_ar,
            'localized_name' => $question->localized_name,
            'q_type' => $question->q_type,
            'question_type' => $question->question_type,
            'options' => $question->options ?? [],
            'score_value' => $question->score_value,
            'allow_manual_score' => $question->allow_manual_score,
            'sort_order' => $question->sort_order,
            'is_required' => $question->is_required,
        ];
    }
}