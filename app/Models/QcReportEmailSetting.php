<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcReportEmailSetting extends Model
{
    protected $table = 'qc_report_email_settings';

    protected $fillable = [
        'company_id',
        'to_emails',
        'cc_emails',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
