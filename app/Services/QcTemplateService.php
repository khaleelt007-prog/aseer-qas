<?php

namespace App\Services;

use App\Models\QcTemplate;
use App\Models\QcAnswer;
use App\Models\QualityEvaluation;
use App\Models\Branch;

class QcTemplateService
{
    /**
     * Check if a branch's brand has an active template.
     *
     * @param int $branchId
     * @return bool
     */
    public function hasActiveTemplate(int $branchId): bool
    {
        $branch = Branch::find($branchId);
        
        if (!$branch || !$branch->brand_id) {
            return false;
        }

        return $this->activeTemplateQueryForBranch($branch)->exists();
    }

    /**
     * Get the active template for a branch's brand.
     *
     * @param int $branchId
     * @return QcTemplate|null
     */
    public function getActiveTemplate(int $branchId): ?QcTemplate
    {
        $branch = Branch::find($branchId);
        
        if (!$branch || !$branch->brand_id) {
            return null;
        }

        return $this->activeTemplateQueryForBranch($branch)
            ->with(['sections.questions'])
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * Get template data formatted for frontend.
     *
     * @param int $branchId
     * @return array|null
     */
    public function getTemplateForBranch(int $branchId): ?array
    {
        $template = $this->getActiveTemplate($branchId);

        if (!$template) {
            return null;
        }

        return [
            'id' => $template->id,
            'name' => $template->name_en,
            'name_ar' => $template->name_ar,
            'localized_name' => $template->localized_name,
            'answer_type' => $template->answer_type,
            'sections' => $template->sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'name_ar' => $section->name_ar,
                    'localized_name' => $section->localized_name,
                    'sort_order' => $section->sort_order,
                    'questions' => $section->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'q_type' => $question->q_type,
                            'question_type' => $question->question_type,
                            'name' => $question->name,
                            'name_ar' => $question->name_ar,
                            'localized_name' => $question->localized_name,
                            'options' => $question->options ?? [],
                            'score_value' => $question->score_value,
                            'allow_manual_score' => $question->allow_manual_score,
                            'sort_order' => $question->sort_order,
                            'is_required' => $question->is_required,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Store checklist answers for an evaluation.
     * For point-based questions (q_type=1), stores the point value directly.
     * Deletes existing answers before storing new ones to prevent duplicates.
     *
     * @param QualityEvaluation $evaluation
     * @param array $answers
     * @return void
     */
    public function storeAnswers(QualityEvaluation $evaluation, array $answers): void
    {
        // Delete existing answers for this evaluation to prevent duplicates
        QcAnswer::where('quality_evaluation_id', $evaluation->id)->delete();

        foreach ($answers as $questionId => $answerValue) {
            if (is_array($answerValue)) {
                $answerValue = json_encode(array_values($answerValue), JSON_UNESCAPED_UNICODE);
            }

            // For point-based questions (q_type=1), store the point value directly
            // The scoring calculation will handle converting these to total points
            QcAnswer::create([
                'quality_evaluation_id' => $evaluation->id,
                'question_id' => $questionId,
                'answer_value' => $answerValue,
            ]);
        }
    }

    /**
     * Get answers for an evaluation formatted for display.
     *
     * @param QualityEvaluation $evaluation
     * @return array
     */
    public function getAnswersForEvaluation(QualityEvaluation $evaluation): array
    {
        $answers = $evaluation->answers()
            ->with('question.section')
            ->get();

        $result = [];
        foreach ($answers as $answer) {
            $result[$answer->question_id] = $answer->answer_value;
        }

        return $result;
    }

    private function activeTemplateQueryForBranch(Branch $branch)
    {
        return QcTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($branch) {
                $query->whereHas('countries', fn ($countryQuery) => $countryQuery->whereKey($branch->country_id))
                    ->orWhereDoesntHave('countries');
            })
            ->where(function ($query) use ($branch) {
                $query->whereHas('brands', fn ($brandQuery) => $brandQuery->whereKey($branch->brand_id))
                    ->orWhere('brand_id', $branch->brand_id);
            });
    }
}

