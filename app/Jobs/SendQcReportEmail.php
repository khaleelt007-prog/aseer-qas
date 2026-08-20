<?php

namespace App\Jobs;

use App\Models\QualityEvaluation;
use App\Services\QcReportEmailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendQcReportEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $qualityEvaluationId)
    {
        $this->onQueue('qc-emails');
        $this->afterCommit();
    }

    public function handle(QcReportEmailSender $sender): void
    {
        $evaluation = QualityEvaluation::query()
            ->with('branch.company.qcReportEmailSetting')
            ->find($this->qualityEvaluationId);

        if (! $evaluation) {
            return;
        }

        $sender->send($evaluation);
    }

    public function backoff(): array
    {
        return [60, 300];
    }
}
