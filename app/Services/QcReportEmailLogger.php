<?php

namespace App\Services;

use App\Models\QcReportEmailLog;
use App\Models\QcReportEmailSetting;
use App\Models\QualityEvaluation;

class QcReportEmailLogger
{
    public function record(QualityEvaluation $evaluation, QcReportEmailSetting $setting): QcReportEmailLog
    {
        return QcReportEmailLog::query()->create([
            'quality_evaluation_id' => $evaluation->id,
            'company_id' => $setting->company_id,
            'to_emails' => $setting->to_emails,
            'cc_emails' => $setting->cc_emails ?? [],
            'sent_at' => now(),
        ]);
    }
}
