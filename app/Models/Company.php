<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    public $timestamps = false;

    protected $table = 'sma_company';

    protected $fillable = [
        'name',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function qcReportEmailSetting(): HasOne
    {
        return $this->hasOne(QcReportEmailSetting::class);
    }

    public function qcReportEmailLogs(): HasMany
    {
        return $this->hasMany(QcReportEmailLog::class);
    }
}
