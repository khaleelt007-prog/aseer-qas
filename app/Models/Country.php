<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    public $timestamps = false;
    protected $table = "sma_countries";

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
     * The Arabic name is stored in the `name2` column on sma_countries.
     */
    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? ($this->name2 ?? $this->name) : $this->name;
    }

    /**
     * Get the branches for this country.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the user access records for this country.
     */
    public function userAccess(): HasMany
    {
        return $this->hasMany(UserAccess::class);
    }

    /**
     * QC templates assigned to this country.
     */
    public function qcTemplates(): BelongsToMany
    {
        return $this->belongsToMany(QcTemplate::class, 'qc_template_country', 'country_id', 'template_id')
            ->withTimestamps();
    }
}
