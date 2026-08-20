<?php

namespace App\Services;

use App\Mail\QcReportMail;
use App\Models\QualityEvaluation;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class QcReportEmailSender
{
    public function __construct(private readonly QcReportEmailLogger $logger) {}

    public function send(QualityEvaluation $evaluation): bool
    {
        if ($evaluation->status !== 'completed' || ! $evaluation->pdf_filename) {
            return false;
        }

        $setting = $evaluation->branch?->company?->qcReportEmailSetting;

        if (! $setting?->is_active || empty($setting->to_emails)) {
            return false;
        }

        $pdfPath = storage_path('app/public/pdfs/'.basename($evaluation->pdf_filename));

        if (! is_file($pdfPath) || ! is_readable($pdfPath)) {
            throw new RuntimeException("QC report PDF is unavailable for evaluation {$evaluation->id}.");
        }

        Mail::to($setting->to_emails)
            ->cc($setting->cc_emails ?? [])
            ->send(new QcReportMail($evaluation, $pdfPath));

        $this->logger->record($evaluation, $setting);

        return true;
    }
}
