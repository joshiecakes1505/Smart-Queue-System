<?php

namespace App\Console\Commands;

use App\Services\QueueService;
use Illuminate\Console\Command;

class ReinstateSkippedQueues extends Command
{
    protected $signature = 'queues:auto-reinstate';

    protected $description = 'Automatically return skipped queues to the waiting line, or expire them once they exhaust their reinstatement attempts';

    public function handle(QueueService $queueService): int
    {
        $result = $queueService->autoReinstateSweep();

        $this->info("Auto-reinstatement sweep complete: {$result['reinstated']} reinstated, {$result['expired']} expired.");

        return self::SUCCESS;
    }
}
