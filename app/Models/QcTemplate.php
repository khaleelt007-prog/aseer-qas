<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class QcTemplate extends Model
{
    protected $table = 'qc_templates';
    public $timestamps = true;

    protected $fillable = [
        'brand_id',
        'name_en',
        'name_ar',
        'is_active',
        'answer_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the brand that owns this template.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Countries assigned to this template.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'qc_template_country', 'template_id', 'country_id')
            ->withTimestamps();
    }

    /**
     * Brands assigned to this template.
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'qc_template_brand', 'template_id', 'brand_id')
            ->withTimestamps();
    }

    /**
     * Get the sections for this template.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(QcSection::class, 'template_id')->orderBy('sort_order');
    }

    /**
     * Get the localized name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Get all questions for this template through sections.
     */
    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(
            QcQuestion::class,
            QcSection::class,
            'template_id',
            'section_id'
        );
    }
}

