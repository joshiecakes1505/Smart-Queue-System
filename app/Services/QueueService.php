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

    public function __construct(?QueueRepository $repo = null)
    {
        $this->repo = $repo ?? new QueueRepository();
    }

    /**
     * Create a queue entry with a transactional, unique queue number.
     */
    public function createQueue(array $data): Queue
    {
        return DB::transaction(function () use ($data) {
            $date = now()->toDateString();
            $serviceCategoryId = $data['service_category_id'] ?? null;

            $counterQuery = QueueCounter::where('date', $date)
                ->where('service_category_id', $serviceCategoryId);

            $counter = $counterQuery->lockForUpdate()->first();

            if (!$counter) {
                $counter = QueueCounter::create([
                    'date' => $date,
                    'service_category_id' => $serviceCategoryId,
                    'last_number' => 0,
                ]);
            }

            $counter->last_number = $counter->last_number + 1;
            $counter->save();

            $number = str_pad($counter->last_number, 4, '0', STR_PAD_LEFT);
            $queueNumber = sprintf('BEC-%s-%s', now()->format('Y'), $number);

            $queue = $this->repo->create([
                'queue_number' => $queueNumber,
                'service_category_id' => $serviceCategoryId,
                'status' => Queue::STATUS_WAITING,
                'client_name' => $data['client_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // initial creation log is optional; queue_logs enum currently contains called/skipped/recalled/completed
            return $queue;
        });
    }

    /**
     * Assign next waiting queue to a window and mark as called.
     */
    public function callNext(int $windowId, ?int $serviceCategoryId = null, ?int $performedBy = null): ?Queue
    {
        return DB::transaction(function () use ($windowId, $serviceCategoryId, $performedBy) {
            $next = $this->repo->findNextWaiting($serviceCategoryId);
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

        $queue->status = Queue::STATUS_SKIPPED;
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

        $queue->status = Queue::STATUS_WAITING;
        $queue->cashier_window_id = null;
        $queue->save();

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
}
