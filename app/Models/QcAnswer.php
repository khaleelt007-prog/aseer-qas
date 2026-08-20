<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QcAnswer extends Model
{
    protected $table = 'qc_answers';
    public $timestamps = true;

    protected $fillable = [
        'quality_evaluation_id',
        'question_id',
        'answer_value',
        'achieved_score',
        'max_score',
    ];

    protected $casts = [
        'achieved_score' => 'float',
        'max_score' => 'float',
    ];

    /**
     * Get the quality evaluation that owns this answer.
     */
    public function qualityEvaluation(): BelongsTo
    {
        return $this->belongsTo(QualityEvaluation::class, 'quality_evaluation_id');
    }

    /**
     * Get the question for this answer.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QcQuestion::class, 'question_id');
    }

    /**
     * Get the follow-up record for this answer.
     */
    public function followUp(): HasOne
    {
        return $this->hasOne(QcAnswerFollowUp::class, 'qc_answer_id');
    }
}

