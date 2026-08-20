<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcAnswerFollowUp extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_SOLVED = 'solved';
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'qc_answer_follow_ups';

    protected $fillable = [
        'qc_answer_id',
        'quality_evaluation_id',
        'question_id',
        'section_id',
        'expected_deadline',
        'status',
        'solved_at',
        'skipped_at',
        'last_commented_at',
        'created_by',
    ];

    protected $casts = [
        'expected_deadline' => 'date',
        'solved_at' => 'datetime',
        'skipped_at' => 'datetime',
        'last_commented_at' => 'datetime',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(QcAnswer::class, 'qc_answer_id');
    }

    public function qualityEvaluation(): BelongsTo
    {
        return $this->belongsTo(QualityEvaluation::class, 'quality_evaluation_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QcQuestion::class, 'question_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QcSection::class, 'section_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(QcAnswerFollowUpComment::class, 'follow_up_id')
            ->orderBy('comment_date')
            ->orderBy('id');
    }
}