<?php

namespace App\Console\Commands;

use App\Services\QualityEvaluationFollowUpService;
use Illuminate\Console\Command;

class ProcessQualityEvaluationWarningsCommand extends Command
{
    protected $signature = 'quality-evaluations:process-warnings';

    protected $description = 'Process overdue quality evaluation follow-ups and sync warning flags';

    public function handle(QualityEvaluationFollowUpService $followUpService): int
    {
        $result = $followUpService->syncWarningFlagsForOverdueFollowUps();

        $this->info(sprintf(
            'Quality evaluation warnings processed. Flagged: %d, Cleared: %d',
            $result['flagged'],
            $result['cleared'],
        ));

        return self::SUCCESS;
    }
}