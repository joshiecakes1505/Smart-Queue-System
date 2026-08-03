<?php

namespace App\Console\Commands;

use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Console\Command;

class ReinstateSkippedQueues extends Command
{
    protected $signature = 'queues:auto-reinstate';

    protected $description = 'Automatically return skipped queues to the waiting line, or expire them once they exhaust their reinstatement attempts';

    public function handle(QueueService $queueService): int
    {
        $delayMinutes = (int) config('ticketing.reinstatement_delay_minutes', 5);
        $maxReinstatements = (int) config('ticketing.max_reinstatements', 2);
        $cutoff = now()->subMinutes($delayMinutes);

        $candidates = Queue::query()
            ->where('status', Queue::STATUS_SKIPPED)
            ->where('is_reinstated', false)
            ->whereNotNull('last_skipped_at')
            ->where('last_skipped_at', '<=', $cutoff)
            ->get();

        $reinstated = 0;
        $expired = 0;

        foreach ($candidates as $queue) {
            if ((int) $queue->skip_count > $maxReinstatements) {
                if ($queueService->expire($queue->id)) {
                    $expired++;
                }
                continue;
            }

            if ($queueService->reinstate($queue->id, null, auto: true)) {
                $reinstated++;
            }
        }

        $this->info("Auto-reinstatement sweep complete: {$reinstated} reinstated, {$expired} expired.");

        return self::SUCCESS;
    }
}
