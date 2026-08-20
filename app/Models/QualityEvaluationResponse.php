<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityEvaluationResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_evaluation_id',
        'evaluation_item_id',
        'achieved_score',
        'max_score',
        'weight',
    ];

    protected $casts = [
        'achieved_score' => 'integer',
        'max_score' => 'integer',
        'weight' => 'decimal:2',
    ];

    /**
     * Get the quality evaluation that owns this response.
     */
    public function qualityEvaluation(): BelongsTo
    {
        return $this->belongsTo(QualityEvaluation::class);
    }

    /**
     * Get the evaluation item for this response.
     */
    public function evaluationItem(): BelongsTo
    {
        return $this->belongsTo(EvaluationItem::class);
    }

    /**
     * Calculate the percentage score for this response.
     */
    public function getPercentageAttribute(): float
    {
        if ($this->achieved_score === null || $this->max_score <= 0) {
            return 0;
        }

        return round(($this->achieved_score / $this->max_score) * 100, 2);
    }

    /**
     * Calculate the weighted score for this response.
     */
    public function getWeightedScoreAttribute(): float
    {
        return round(($this->percentage * $this->weight) / 100, 2);
    }

    /**
     * Check if this response has been completed (has an achieved score).
     */
    public function isComplete(): bool
    {
        return $this->achieved_score !== null;
    }
}
