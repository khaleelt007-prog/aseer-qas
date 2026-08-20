<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    public $timestamps = false;
    protected $table = "sma_brands";

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name2',
        'code',
    ];

    /**
     * Get the localized name based on current locale.
     * The Arabic name is stored in the `name2` column on sma_brands.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? ($this->name2 ?? $this->name) : $this->name;
    }

    /**
     * Get the branches for this brand.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the user access records for this brand.
     */
    public function userAccess(): HasMany
    {
        return $this->hasMany(UserAccess::class);
    }

    /**
     * QC templates assigned to this brand.
     */
    public function qcTemplates(): BelongsToMany
    {
        return $this->belongsToMany(QcTemplate::class, 'qc_template_brand', 'brand_id', 'template_id')
            ->withTimestamps();
    }
}
