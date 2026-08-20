<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    public $timestamps = false;
    protected $table = "sma_branches";
    protected $fillable = [
        'name',
        'name_ar',
        'company_id',
    ];

    /**
     * Get the localized name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar ?? $this->name : $this->name;
    }

    /**
     * Get the brand that owns this branch.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the country that owns this branch.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the company that owns this branch.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the quality evaluations for this branch.
     */
    public function qualityEvaluations(): HasMany
    {
        return $this->hasMany(QualityEvaluation::class);
    }
}
