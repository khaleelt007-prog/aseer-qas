<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcSection extends Model
{
    protected $table = 'qc_sections';
    public $timestamps = true;

    protected $fillable = [
        'template_id',
        'name',
        'name_ar',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the template that owns this section.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QcTemplate::class, 'template_id');
    }

    /**
     * Get the questions for this section.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QcQuestion::class, 'section_id')->orderBy('sort_order');
    }

    /**
     * Get the photos for this section.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(QualityEvaluationPhoto::class, 'section_id');
    }

    /**
     * Get the localized name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name;
    }
}

