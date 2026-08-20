<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Branch;
use App\Models\Country;
use App\Models\QcAnswer;
use App\Models\QcAnswerFollowUp;
use App\Models\QcQuestion;
use App\Models\QualityEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class QualityEvaluationFollowUpService
{
    public function __construct(private readonly DataAccessService $dataAccessService)
    {
    }

    public function getEligibleEvaluationsQuery(User $user): Builder
    {
        $query = ((int) $user->group_id === 1)
            ? QualityEvaluation::query()
            : QualityEvaluation::query()->where('user_id', $user->id);

        $this->dataAccessService->applyBranchFilterFromSession($query);

        return $query->checklistFollowUpEligible();
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['country_id'])) {
            $countryBranchIds = Branch::where('country_id', $filters['country_id'])->pluck('id');
            $query->whereIn('branch_id', $countryBranchIds);
        }

        if (!empty($filters['brand_id'])) {
            $brandBranchIds = Branch::where('brand_id', $filters['brand_id'])->pluck('id');
            $query->whereIn('branch_id', $brandBranchIds);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query;
    }

    public function appendFollowUpCounts(Builder $query): Builder
    {
        return $query->withCount([
            'answers as bad_answers_count' => function (Builder $answerQuery) {
                $answerQuery
                    ->whereIn('answer_value', ['0', '0.5'])
                    ->whereHas('question', function (Builder $questionQuery) {
                        $questionQuery->where('q_type', QcQuestion::TYPE_POINT_BASED);
                    });
            },
            'followUps as open_follow_ups_count' => fn (Builder $followUps) => $followUps->where('status', QcAnswerFollowUp::STATUS_OPEN),
            'followUps as solved_follow_ups_count' => fn (Builder $followUps) => $followUps->where('status', QcAnswerFollowUp::STATUS_SOLVED),
            'followUps as skipped_follow_ups_count' => fn (Builder $followUps) => $followUps->where('status', QcAnswerFollowUp::STATUS_SKIPPED),
            'followUps as overdue_follow_ups_count' => fn (Builder $followUps) => $followUps
                ->where('status', QcAnswerFollowUp::STATUS_OPEN)
                ->whereNotNull('expected_deadline')
                ->whereDate('expected_deadline', '<', now()->toDateString()),
        ]);
    }

    public function getFilterData(): array
    {
        $branchQuery = Branch::where('is_catering', 0);
        $this->dataAccessService->applyBranchFilterFromSession($branchQuery, 'id');

        $branches = $branchQuery->get()->map(fn (Branch $branch) => [
            'id' => $branch->id,
            'name' => $branch->name,
            'name_ar' => $branch->name_ar,
            'localized_name' => $branch->localized_name,
            'country_id' => $branch->country_id,
            'brand_id' => $branch->brand_id,
        ]);

        $countries = Country::whereIn('id', $branches->pluck('country_id')->unique()->filter()->values()->all())
            ->get()
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'name' => $country->name,
                'name_ar' => $country->name_ar,
                'localized_name' => $country->localized_name,
            ]);

        $brands = Brand::whereIn('id', $branches->pluck('brand_id')->unique()->filter()->values()->all())
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'name_ar' => $brand->name_ar,
                'localized_name' => $brand->localized_name,
            ]);

        return compact('branches', 'countries', 'brands');
    }

    public function isEligibleEvaluation(QualityEvaluation $evaluation): bool
    {
        return $evaluation->type === 'checklist'
            && $evaluation->status === 'completed'
            && $evaluation->hasBadChecklistAnswers();
    }

    public function isBadAnswer(QcAnswer $answer): bool
    {
        return $answer->question
            && $answer->question->q_type === QcQuestion::TYPE_POINT_BASED
            && in_array($this->normalizeAnswerValue($answer->answer_value), ['0', '0.5'], true);
    }

    public function buildShowPayload(QualityEvaluation $evaluation): array
    {
        $evaluation->load([
            'branch',
            'photos',
            'template.sections.questions',
            'answers.question.section',
            'answers.followUp.comments.createdBy',
        ]);

        if ($evaluation->branch) {
            $evaluation->branch->append('localized_name');
        }

        $answers = $evaluation->answers;
        $sections = [];

        foreach ($evaluation->template?->sections ?? [] as $section) {
            $badAnswers = $answers
                ->filter(fn (QcAnswer $answer) => $answer->question?->section_id === $section->id && $this->isBadAnswer($answer))
                ->sortBy(fn (QcAnswer $answer) => $answer->question?->sort_order ?? PHP_INT_MAX)
                ->values();

            if ($badAnswers->isEmpty()) {
                continue;
            }

            $sectionComments = $answers
                ->filter(fn (QcAnswer $answer) => $answer->question?->section_id === $section->id && $answer->question?->q_type === QcQuestion::TYPE_TEXT)
                ->sortBy(fn (QcAnswer $answer) => $answer->question?->sort_order ?? PHP_INT_MAX)
                ->values()
                ->map(fn (QcAnswer $answer) => [
                    'answer_id' => $answer->id,
                    'question_id' => $answer->question_id,
                    'question' => $answer->question?->localized_name,
                    'answer_value' => $answer->answer_value,
                ]);

            $photos = $evaluation->photos
                ->where('section_id', $section->id)
                ->values()
                ->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'original_filename' => $photo->original_filename,
                    'formatted_file_size' => $photo->formatted_file_size,
                    'uploaded_at' => $photo->uploaded_at,
                ]);

            $sections[] = [
                'id' => $section->id,
                'localized_name' => $section->localized_name,
                'bad_questions' => $badAnswers->map(fn (QcAnswer $answer) => $this->formatBadAnswer($answer, $evaluation->template?->answer_type))->all(),
                'comment_questions' => $sectionComments->all(),
                'photos' => $photos->all(),
            ];
        }

        return [
            'evaluation' => $evaluation,
            'followUpData' => [
                'template_name' => $evaluation->template?->localized_name,
                'answer_type' => $evaluation->template?->answer_type ?? 'Points',
                'sections' => $sections,
            ],
        ];
    }

    public function setDeadline(QcAnswer $answer, string $deadline, ?User $user = null): QcAnswerFollowUp
    {
        $followUp = $this->getOrCreateFollowUp($answer, $user);
        $followUp->expected_deadline = $deadline;

        if (!$followUp->status) {
            $followUp->status = QcAnswerFollowUp::STATUS_OPEN;
        }

        $followUp->save();
        $this->syncWarningFlagForEvaluation($followUp->qualityEvaluation);

        return $followUp;
    }

    public function addComment(QcAnswer $answer, string $commentType, string $commentText, User $user): QcAnswerFollowUp
    {
        $followUp = $this->getOrCreateFollowUp($answer, $user);

        $followUp->comments()->create([
            'comment_type' => $commentType,
            'comment_date' => now(),
            'comment_text' => $commentText,
            'created_by' => $user->id,
        ]);

        $followUp->forceFill(['last_commented_at' => now()])->save();

        return $followUp->load('comments.createdBy');
    }

    public function markSolved(QcAnswer $answer, ?User $user = null): QcAnswerFollowUp
    {
        $followUp = $this->getOrCreateFollowUp($answer, $user);
        $followUp->forceFill([
            'status' => QcAnswerFollowUp::STATUS_SOLVED,
            'solved_at' => now(),
            'skipped_at' => null,
        ])->save();

        $this->syncWarningFlagForEvaluation($followUp->qualityEvaluation);

        return $followUp;
    }

    public function markSkipped(QcAnswer $answer, ?User $user = null): QcAnswerFollowUp
    {
        $followUp = $this->getOrCreateFollowUp($answer, $user);
        $followUp->forceFill([
            'status' => QcAnswerFollowUp::STATUS_SKIPPED,
            'skipped_at' => now(),
            'solved_at' => null,
        ])->save();

        $this->syncWarningFlagForEvaluation($followUp->qualityEvaluation);

        return $followUp;
    }

    public function syncWarningFlagForEvaluation(QualityEvaluation $evaluation): void
    {
        $evaluation = $evaluation->fresh();

        if (!$evaluation) {
            return;
        }

        $shouldWarn = $evaluation?->hasOverdueOpenFollowUps() ?? false;

        $evaluation->forceFill([
            'warning_flag' => $shouldWarn,
            'warning_flagged_at' => $shouldWarn
                ? ($evaluation->warning_flagged_at ?? now())
                : null,
        ])->save();
    }

    public function syncWarningFlagsForOverdueFollowUps(): array
    {
        $flagged = 0;
        $cleared = 0;

        QualityEvaluation::query()
            ->where('type', 'checklist')
            ->where('status', 'completed')
            ->chunkById(100, function ($evaluations) use (&$flagged, &$cleared) {
                foreach ($evaluations as $evaluation) {
                    $shouldWarn = $evaluation->hasOverdueOpenFollowUps();

                    if ($shouldWarn && !$evaluation->warning_flag) {
                        $flagged++;
                    }

                    if (!$shouldWarn && $evaluation->warning_flag) {
                        $cleared++;
                    }

                    $evaluation->forceFill([
                        'warning_flag' => $shouldWarn,
                        'warning_flagged_at' => $shouldWarn
                            ? ($evaluation->warning_flagged_at ?? now())
                            : null,
                    ])->save();
                }
            });

        return compact('flagged', 'cleared');
    }

    public function getOrCreateFollowUp(QcAnswer $answer, ?User $user = null): QcAnswerFollowUp
    {
        $answer->loadMissing('question.section', 'qualityEvaluation');

        return QcAnswerFollowUp::firstOrCreate(
            ['qc_answer_id' => $answer->id],
            [
                'quality_evaluation_id' => $answer->quality_evaluation_id,
                'question_id' => $answer->question_id,
                'section_id' => $answer->question?->section_id,
                'status' => QcAnswerFollowUp::STATUS_OPEN,
                'created_by' => $user?->id,
            ]
        );
    }

    private function formatBadAnswer(QcAnswer $answer, ?string $answerType): array
    {
        $followUp = $answer->followUp;

        return [
            'answer_id' => $answer->id,
            'question_id' => $answer->question_id,
            'question' => $answer->question?->localized_name,
            'answer_value' => $answer->answer_value,
            'answer_label' => $this->formatAnswerLabel($answerType, $answer->answer_value),
            'severity' => $this->normalizeAnswerValue($answer->answer_value) === '0' ? 'high' : 'medium',
            'follow_up' => $followUp ? [
                'id' => $followUp->id,
                'status' => $followUp->status,
                'expected_deadline' => optional($followUp->expected_deadline)->toDateString(),
                'solved_at' => $followUp->solved_at,
                'skipped_at' => $followUp->skipped_at,
                'last_commented_at' => $followUp->last_commented_at,
                'comments' => $followUp->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'comment_type' => $comment->comment_type,
                    'comment_text' => $comment->comment_text,
                    'comment_date' => $comment->comment_date,
                    'author_name' => $this->resolveUserDisplayName($comment->createdBy),
                ])->all(),
            ] : null,
        ];
    }

    private function normalizeAnswerValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return trim((string) $value);
        }

        $normalized = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($normalized, '0'), '.');
    }

    private function formatAnswerLabel(?string $answerType, mixed $value): string
    {
        $normalized = $this->normalizeAnswerValue($value);

        if ($answerType === 'Yes/No') {
            return $normalized === '0' ? 'No' : 'Yes';
        }

        return $normalized === '0' ? '0 PT' : '0.5 PT';
    }

    private function resolveUserDisplayName(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $fullName !== '' ? $fullName : ($user->username ?? $user->email);
    }
}