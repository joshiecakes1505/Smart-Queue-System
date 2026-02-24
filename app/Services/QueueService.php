<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\QueueCounter;
use App\Models\QueueLog;
use App\Repositories\QueueRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueService
{
    protected ?QueueRepository $repo = null;
    protected QueueSchedulingService $scheduler;

    public function __construct(?QueueRepository $repo = null, ?QueueSchedulingService $scheduler = null)
    {
        $this->repo = $repo ?? new QueueRepository();
        $this->scheduler = $scheduler ?? new QueueSchedulingService();
    }

    /**
     * Create a queue entry with a transactional, unique queue number.
     */
    public function createQueue(array $data): Queue
    {
        return DB::transaction(function () use ($data) {
            $date = now()->toDateString();
            $serviceCategoryId = $data['service_category_id'] ?? null;

            // Get the service category to use its prefix
            $serviceCategory = \App\Models\ServiceCategory::find($serviceCategoryId);
            $prefix = $serviceCategory?->prefix ?? 'Q';

            $counterQuery = QueueCounter::whereDate('date', $date)
                ->where('service_category_id', $serviceCategoryId);

            $counter = $counterQuery->lockForUpdate()->first();

            if (!$counter) {
                $counter = QueueCounter::create([
                    'date' => $date,
                    'service_category_id' => $serviceCategoryId,
                    'last_number' => 0,
                ]);
            }

            $queueNumber = $this->nextAvailableQueueNumber($counter, $prefix);

            $assignedWindowId = $serviceCategoryId
                ? $this->scheduler->assignWindowForIncomingQueue((int) $serviceCategoryId, $counter)
                : null;

            $queue = $this->repo->create([
                'queue_number' => $queueNumber,
                'service_category_id' => $serviceCategoryId,
                'status' => Queue::STATUS_WAITING,
                'client_name' => $data['client_name'] ?? null,
                'client_type' => $data['client_type'] ?? 'student',
                'phone' => $data['phone'] ?? null,
                'note' => $data['note'] ?? null,
                'cashier_window_id' => $assignedWindowId,
            ]);

            // initial creation log is optional; queue_logs enum currently contains called/skipped/recalled/completed
            return $queue;
        });
    }

    private function nextAvailableQueueNumber(QueueCounter $counter, string $prefix): string
    {
        do {
            $counter->last_number = $counter->last_number + 1;
            $counter->save();

            $number = str_pad($counter->last_number, 3, '0', STR_PAD_LEFT);
            $queueNumber = sprintf('%s-%s', $prefix, $number);
        } while (Queue::where('queue_number', $queueNumber)->exists());

        return $queueNumber;
    }

    /**
     * Assign next waiting queue to a window and mark as called.
     */
    public function callNext(int $windowId, ?int $serviceCategoryId = null, ?int $performedBy = null): ?Queue
    {
        return DB::transaction(function () use ($windowId, $serviceCategoryId, $performedBy) {
            $activeQueue = Queue::where('cashier_window_id', $windowId)
                ->where('status', Queue::STATUS_CALLED)
                ->orderBy('start_time', 'desc')
                ->first();

            if ($activeQueue) {
                return $activeQueue;
            }

            $counter = $this->resolveSchedulingCounter($serviceCategoryId);

            $next = $this->scheduler->selectNextQueueForWindow($windowId, $serviceCategoryId, $counter);
            if (!$next) {
                return null;
            }

            $next->status = Queue::STATUS_CALLED;
            $next->cashier_window_id = $windowId;
            $next->start_time = Carbon::now();
            $next->save();

            QueueLog::create([
                'queue_id' => $next->id,
                'action' => 'called',
                'performed_by' => $performedBy,
                'meta' => ['window_id' => $windowId],
            ]);

            return $next;
        });
    }

    public function skip(int $queueId, ?int $performedBy = null): ?Queue
    {
        $queue = $this->repo->getById($queueId);
        if (!$queue) return null;

        if ($queue->status !== Queue::STATUS_CALLED) {
            return null;
        }

        $queue->skip_count = (int) $queue->skip_count + 1;
        $queue->status = Queue::STATUS_SKIPPED;
        $queue->cashier_window_id = null;
        $queue->end_time = Carbon::now();
        $queue->save();

        QueueLog::create([
            'queue_id' => $queue->id,
            'action' => 'skipped',
            'performed_by' => $performedBy,
        ]);

        return $queue;
    }

    public function recall(int $queueId, ?int $performedBy = null): ?Queue
    {
        $queue = $this->repo->getById($queueId);
        if (!$queue) return null;

        $queue->touch();

        QueueLog::create([
            'queue_id' => $queue->id,
            'action' => 'recalled',
            'performed_by' => $performedBy,
        ]);

        return $queue;
    }

    public function complete(int $queueId, ?int $performedBy = null): ?Queue
    {
        $queue = $this->repo->getById($queueId);
        if (!$queue) return null;

        if ($queue->status !== Queue::STATUS_CALLED) {
            return null;
        }

        $queue->status = Queue::STATUS_COMPLETED;
        if (!$queue->start_time) {
            $queue->start_time = Carbon::now();
        }
        $queue->end_time = Carbon::now();
        $queue->save();

        QueueLog::create([
            'queue_id' => $queue->id,
            'action' => 'completed',
            'performed_by' => $performedBy,
            'meta' => [
                'duration_seconds' => $queue->end_time->diffInSeconds($queue->start_time),
            ],
        ]);

        return $queue;
    }

    public function reinstate(int $queueId, ?int $performedBy = null): ?Queue
    {
        $queue = $this->repo->getById($queueId);
        if (!$queue) return null;

        if (
            $queue->status !== Queue::STATUS_SKIPPED ||
            (int) $queue->skip_count !== 1 ||
            (bool) $queue->is_reinstated
        ) {
            return null;
        }

        $queue->status = Queue::STATUS_WAITING;
        $queue->is_reinstated = true;
        $queue->cashier_window_id = null;
        $queue->start_time = null;
        $queue->end_time = null;
        $queue->save();

        QueueLog::create([
            'queue_id' => $queue->id,
            'action' => 'reinstated',
            'performed_by' => $performedBy,
        ]);

        return $queue;
    }

    public function estimateWaitMinutes(Queue $queue): ?int
    {
        return $this->scheduler->estimateWaitMinutes($queue);
    }

    private function resolveSchedulingCounter(?int $serviceCategoryId): QueueCounter
    {
        $date = now()->toDateString();

        $counterQuery = QueueCounter::whereDate('date', $date);

        if ($serviceCategoryId) {
            $counterQuery->where('service_category_id', $serviceCategoryId);
        } else {
            $counterQuery->whereNull('service_category_id');
        }

        $counter = $counterQuery->lockForUpdate()->first();

        if (!$counter) {
            $counter = QueueCounter::create([
                'date' => $date,
                'service_category_id' => $serviceCategoryId,
                'last_number' => 0,
                'regular_served_in_cycle' => 0,
            ]);
        }

        return $counter;
    }
}
