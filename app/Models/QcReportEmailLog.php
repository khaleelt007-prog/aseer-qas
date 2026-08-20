<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcReportEmailLog extends Model
{
    protected $table = 'qc_report_email_logs';

    protected $fillable = [
        'quality_evaluation_id',
        'company_id',
        'to_emails',
        'cc_emails',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function qualityEvaluation(): BelongsTo
    {
        return $this->belongsTo(QualityEvaluation::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
