<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcQuestion extends Model
{
    protected $table = 'qc_questions';
    public $timestamps = true;

    // Question type constants
    const TYPE_POINT_BASED = 1;  // Changed from TYPE_YES_NO to TYPE_POINT_BASED (1 PT, 0.5 PT, 0 PT, N/A)
    const TYPE_TEXT = 2;

    const QUESTION_TYPE_POINTS = 'points';
    const QUESTION_TYPE_YES_NO = 'yes_no';
    const QUESTION_TYPE_MULTI_SELECT = 'multi_select';
    const QUESTION_TYPE_SCORE = 'score';
    const QUESTION_TYPE_COMMENT = 'comment';

    protected $fillable = [
        'section_id',
        'q_type',
        'question_type',
        'name',
        'name_ar',
        'options',
        'score_value',
        'allow_manual_score',
        'sort_order',
        'is_required'
    ];

    protected $casts = [
        'q_type' => 'integer',
        'options' => 'array',
        'score_value' => 'float',
        'allow_manual_score' => 'boolean',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
    ];

    /**
     * Get the section that owns this question.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(QcSection::class, 'section_id');
    }

    /**
     * Get the answers for this question.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QcAnswer::class, 'question_id');
    }

    /**
     * Get the localized name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name;
    }

    /**
     * Get the question type label.
     */
    public function getTypeLabel(): string
    {
        if ($this->question_type) {
            return match($this->question_type) {
                self::QUESTION_TYPE_POINTS => 'Points (1 PT, 0.5 PT, 0 PT, N/A)',
                self::QUESTION_TYPE_YES_NO => 'Yes/No',
                self::QUESTION_TYPE_MULTI_SELECT => 'Multi-Select',
                self::QUESTION_TYPE_SCORE => 'Score',
                self::QUESTION_TYPE_COMMENT => 'Section Comment',
                default => 'Unknown',
            };
        }

        return match($this->q_type) {
            self::TYPE_POINT_BASED => 'Point-Based (1 PT, 0.5 PT, 0 PT, N/A)',
            self::TYPE_TEXT => 'Text',
            default => 'Unknown',
        };
    }
}

