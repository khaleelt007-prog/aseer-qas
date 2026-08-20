<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\QcQuestion;

class QualityEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'title',
        'total_score',
        'max_score',
        'comments',
        'status',
        'type',
        'template_id',
        'completed_at',
        'extra_points',
        'pdf_filename',
        'warning_flag',
        'warning_flagged_at',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
        'max_score' => 'integer',
        'completed_at' => 'datetime',
        'warning_flag' => 'boolean',
        'warning_flagged_at' => 'datetime',
    ];

    /**
     * Get the user that owns the quality evaluation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch that owns the quality evaluation.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the QC template for this evaluation (if it's a checklist type).
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QcTemplate::class, 'template_id');
    }

    /**
     * Get the responses for this quality evaluation.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(QualityEvaluationResponse::class);
    }

    /**
     * Get the responses with their evaluation items.
     */
    public function responsesWithItems(): HasMany
    {
        return $this->responses()->with('evaluationItem');
    }

    /**
     * Get the photos for this quality evaluation.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(QualityEvaluationPhoto::class);
    }

    /**
     * Get the checklist answers for this quality evaluation.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QcAnswer::class, 'quality_evaluation_id');
    }

    /**
     * Get the follow-up records for checklist answers.
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(QcAnswerFollowUp::class, 'quality_evaluation_id');
    }

    /**
     * Get successful QC report email deliveries for this evaluation.
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(QcReportEmailLog::class, 'quality_evaluation_id')
            ->latest('sent_at');
    }

    /**
     * Scope checklist evaluations that are eligible for follow-up.
     */
    public function scopeChecklistFollowUpEligible(Builder $query): Builder
    {
        return $query
            ->where('type', 'checklist')
            ->where('status', 'completed')
            ->whereHas('answers', function (Builder $answersQuery) {
                $answersQuery
                    ->whereIn('answer_value', ['0', '0.5'])
                    ->whereHas('question', function (Builder $questionQuery) {
                        $questionQuery->where('q_type', QcQuestion::TYPE_POINT_BASED);
                    });
            });
    }

    /**
     * Scope evaluations with warning items first.
     */
    public function scopeWarningFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('warning_flag')
            ->orderByRaw('COALESCE(warning_flagged_at, completed_at, created_at) DESC');
    }

    /**
     * Check if the evaluation has bad checklist answers.
     */
    public function hasBadChecklistAnswers(): bool
    {
        if (!$this->relationLoaded('answers')) {
            $this->load('answers.question');
        }

        return $this->answers->contains(function (QcAnswer $answer) {
            if (!$answer->question || $answer->question->q_type !== QcQuestion::TYPE_POINT_BASED) {
                return false;
            }

            return in_array((string) $answer->answer_value, ['0', '0.5'], true);
        });
    }

    /**
     * Check if the evaluation has overdue open follow-ups.
     */
    public function hasOverdueOpenFollowUps(): bool
    {
        return $this->followUps()
            ->where('status', QcAnswerFollowUp::STATUS_OPEN)
            ->whereNotNull('expected_deadline')
            ->whereDate('expected_deadline', '<', now()->toDateString())
            ->exists();
    }

    /**
     * Calculate the total weighted score based on individual scores.
     *
     * New behavior for overwrite_total_score_if_zero:
     * When an item with overwrite_total_score_if_zero=true has a score of 0:
     * 1. Calculate total excluding that item
     * 2. Subtract 60 points from the result
     * 3. Return the adjusted score (instead of returning 0)
     *
     * For checklist evaluations with Yes/No answer type:
     * - Yes = 1 point
     * - No = 0 points
     * - N/A = 0 points and excluded from total score calculation
     */
    public function calculateTotalScore(): float
    {
        // Check for zero override conditions first
        $zeroOverrideItem = null;
        foreach ($this->responses as $response) {
            // Check if this item has zero override enabled and achieved score is 0
            // Zero override takes precedence over exclude_from_score
            if ($response->evaluationItem &&
                $response->evaluationItem->overwrite_total_score_if_zero &&
                $response->achieved_score === 0) {
                $zeroOverrideItem = $response;
                break;
            }
        }

        // Normal calculation
        $totalScore = 0;

        foreach ($this->responses as $response) {
            // If we have a zero override item, skip it from calculation
            if ($zeroOverrideItem && $response->id === $zeroOverrideItem->id) {
                continue;
            }

            // Skip items that are excluded from score calculation only if score > 0
            if ($response->evaluationItem &&
                $response->evaluationItem->exclude_from_score &&
                $response->achieved_score > 0) {
                continue;
            }

            if ($response->achieved_score !== null && $response->max_score > 0) {
                $percentage = ($response->achieved_score / $response->max_score) * 100;
                $totalScore += ($percentage * $response->weight) / 100;
            }
        }

        // If we found a zero override item, subtract 60 points from the calculated total
        if ($zeroOverrideItem) {
            $totalScore -= 60;
        }

        return round($totalScore, 2);
    }

    /**
     * Calculate the final total score including extra points.
     *
     * With the new overwrite_total_score_if_zero behavior:
     * - The base score already includes the -60 penalty when applicable
     * - Extra points are added to the adjusted score
     * - Final score is clamped between 0 and 100
     */
    public function getFinalTotalScore(): float
    {
        $baseScore = $this->calculateTotalScore();

        $finalScore = $baseScore + ($this->extra_points ?? 0);

        // Ensure the final score doesn't go below 0 or above 100
        return round(max(0, min(100, $finalScore)), 2);
    }

    /**
     * Calculate checklist score based on template answer type.
     * Returns array with 'numerator' (total points) and 'denominator' (max possible points).
     *
     * For Points answer type:
     * - '1' answers: Award 1 point
     * - '0.5' answers: Award 0.5 points
     * - '0' answers: Award 0 points
     * - N/A (null) answers: Excluded from both numerator and denominator
     *
     * For Yes/No answer type:
     * - '1' (Yes) answers: Award 1 point
     * - '0' (No) answers: Award 0 points
     * - N/A (null) answers: Excluded from both numerator and denominator
     *
     * Empty answers: Excluded from both numerator and denominator
     */
    public function calculateChecklistScore(): array
    {
        // Load answers with questions and template if not already loaded
        if (!$this->relationLoaded('answers')) {
            $this->load('answers.question');
        }
        if (!$this->relationLoaded('template')) {
            $this->load('template');
        }

        if ($this->answers->contains(fn (QcAnswer $answer) => filled($answer->question?->question_type))) {
            return $this->calculateChecklistScorePointBased();
        }

        // Determine scoring method based on legacy template answer type
        $answerType = $this->template?->answer_type ?? 'Points';

        if ($answerType === 'Yes/No') {
            return $this->calculateChecklistScoreYesNo();
        }

        return $this->calculateChecklistScorePointBased();
    }

    /**
     * Calculate checklist score using point-based answers.
     * Returns array with 'numerator' (total points) and 'denominator' (max possible points).
     *
     * Scoring logic:
     * - '1' answers: Award 1 point
     * - '0.5' answers: Award 0.5 points
     * - '0' answers: Award 0 points
     * - N/A (null) answers: Excluded from both numerator and denominator
     * - Empty answers: Excluded from both numerator and denominator
     * - TYPE_TEXT questions: Excluded from both numerator and denominator
     */
    private function calculateChecklistScorePointBased(): array
    {
        $totalPoints = 0;
        $maxPoints = 0;

        foreach ($this->answers as $answer) {
            // Skip empty answers
            if ($answer->answer_value === null || $answer->answer_value === '') {
                continue;
            }

            if ($answer->question?->question_type === QcQuestion::QUESTION_TYPE_MULTI_SELECT) {
                continue;
            }

            if ($answer->question?->question_type === QcQuestion::QUESTION_TYPE_SCORE) {
                $maxScore = (float) ($answer->question->score_value ?: 1);
                $maxPoints += $maxScore;
                $totalPoints += max(0, min((float) $answer->answer_value, $maxScore));
                continue;
            }

            if ($answer->question?->question_type === QcQuestion::QUESTION_TYPE_YES_NO) {
                $maxScore = (float) ($answer->question->score_value ?: 1);
                $maxPoints += $maxScore;
                $totalPoints += ((string) $answer->answer_value === '1') ? $maxScore : 0;
                continue;
            }

            // Skip text-only questions (TYPE_TEXT) - they don't contribute to scoring
            if ($answer->question && $answer->question->q_type === QcQuestion::TYPE_TEXT) {
                continue;
            }

            // Convert answer value to float for point calculation
            $pointValue = (float) $answer->answer_value;

            // Add to max points for each answered question (max is always 1 point per question)
            $maxPoints += 1;

            // Add to total points based on the answer value
            $totalPoints += $pointValue;
        }

        return [
            'numerator' => $totalPoints,
            'denominator' => $maxPoints > 0 ? $maxPoints : 0,
        ];
    }

    /**
     * Calculate checklist score using Yes/No answers.
     * Returns array with 'numerator' (total points) and 'denominator' (max possible points).
     *
     * Scoring logic for Yes/No answer type:
     * - '1' (Yes) answers: Award 1 point
     * - '0' (No) answers: Award 0 points
     * - N/A (null) answers: Excluded from both numerator and denominator
     * - Empty answers: Excluded from both numerator and denominator
     * - TYPE_TEXT questions: Excluded from both numerator and denominator
     */
    private function calculateChecklistScoreYesNo(): array
    {
        $totalPoints = 0;
        $maxPoints = 0;

        foreach ($this->answers as $answer) {
            // Skip empty answers
            if ($answer->answer_value === null || $answer->answer_value === '') {
                continue;
            }

            // Skip text-only questions (TYPE_TEXT) - they don't contribute to scoring
            if ($answer->question && $answer->question->q_type === QcQuestion::TYPE_TEXT) {
                continue;
            }

            // Convert answer value to float for point calculation
            $pointValue = (float) $answer->answer_value;

            // Add to max points for each answered question (max is always 1 point per question)
            $maxPoints += 1;

            // Add to total points based on the answer value (1 for Yes, 0 for No)
            $totalPoints += $pointValue;
        }

        return [
            'numerator' => $totalPoints,
            'denominator' => $maxPoints > 0 ? $maxPoints : 0,
        ];
    }

    /**
     * Get evaluation items with their current scores and weights.
     */
    public function getEvaluationItems(): array
    {
        $items = [];

        foreach ($this->responsesWithItems as $response) {
            $items[] = [
                'id' => $response->evaluationItem->identifier,
                'title' => $response->evaluationItem->name,
                'title_ar' => $response->evaluationItem->name_ar,
                'localized_title' => $response->evaluationItem->localized_name,
                'achieved' => $response->achieved_score,
                'max' => $response->max_score,
                'weight' => $response->weight,
                'exclude_from_score' => $response->evaluationItem->exclude_from_score,
                'overwrite_total_score_if_zero' => $response->evaluationItem->overwrite_total_score_if_zero,
            ];
        }

        return $items;
    }

    /**
     * Check if all evaluation items have been scored.
     */
    public function isComplete(): bool
    {
        foreach ($this->responses as $response) {
            if ($response->achieved_score === null) {
                return false;
            }
        }

        return $this->responses->count() > 0;
    }

    /**
     * Initialize responses for all evaluation items.
     */
    public function initializeResponses(): void
    {
        $evaluationItems = EvaluationItem::all();

        foreach ($evaluationItems as $item) {
            $this->responses()->firstOrCreate(
                ['evaluation_item_id' => $item->id],
                [
                    'achieved_score' => null,
                    'max_score' => $item->weight, // Default max score to weight
                    'weight' => $item->weight,
                ]
            );
        }
    }

    /**
     * Get the PDF URL if a PDF has been generated.
     */
    public function getPdfUrl(): ?string
    {
        if (!$this->pdf_filename) {
            return null;
        }

        return asset('storage/pdfs/' . $this->pdf_filename);
    }

    /**
     * Get the secure PDF URL (requires authentication).
     */
    public function getSecurePdfUrl(): ?string
    {
        if (!$this->pdf_filename) {
            return null;
        }

        return route('quality-evaluations.serve-pdf', [
            'qualityEvaluation' => $this->id,
            'filename' => $this->pdf_filename
        ]);
    }

    /**
     * Check if a PDF has been generated for this evaluation.
     */
    public function hasPdf(): bool
    {
        return !empty($this->pdf_filename) &&
               file_exists(storage_path('app/public/pdfs/' . $this->pdf_filename));
    }
}
