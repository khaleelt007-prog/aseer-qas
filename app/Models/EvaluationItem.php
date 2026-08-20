<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'weight',
        "exclude_from_score",
        'overwrite_total_score_if_zero',
        "sort_order",
    ];

    protected $casts = [
        'weight' => 'integer',
        'exclude_from_score' => 'boolean',
        'overwrite_total_score_if_zero' => 'boolean',
    ];

    /**
     * Get the evaluation item's identifier for form fields.
     * This converts the name to a snake_case identifier.
     */
    public function getIdentifierAttribute(): string
    {
        return str_replace(' ', '_', strtolower($this->name));
    }

    /**
     * Get the localized name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name;
    }

    /**
     * Get the responses for this evaluation item.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(QualityEvaluationResponse::class);
    }
}
