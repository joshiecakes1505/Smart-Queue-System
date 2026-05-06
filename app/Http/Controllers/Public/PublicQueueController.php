<?php

namespace App\Http\Controllers\Public;

use App\Events\DisplayQueuesUpdated;
use App\Http\Controllers\Controller;
use App\Models\CashierWindow;
use App\Models\Queue;
use App\Models\QueueCounter;
use App\Models\QueueLog;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicQueueController extends Controller
{
    private const COMPLETED_QUEUE_LINK_EXPIRY_MINUTES = 5;

    public function __construct(private readonly QueueService $queueService)
    {
    }

    /**
     * Live view of all windows and waiting queues (for public display)
     */
    public function liveView()
    {
        $windows = CashierWindow::with(['assignedUser'])
            ->where('active', true)
            ->get()
            ->map(function ($w) {
                $current = Queue::where('cashier_window_id', $w->id)
                    ->with('serviceCategory')
                    ->where('status', Queue::STATUS_CALLED)
                    ->orderBy('start_time', 'desc')
                    ->first();

                return [
                    'id' => $w->id,
                    'name' => $w->name ?? 'Window ' . $w->id,
                    'assigned_user' => $w->assignedUser?->name,
                    'current' => $current ? [
                        'queue_number' => $current->queue_number,
                        'updated_at' => $current->updated_at?->toIso8601String(),
                        'client_name' => $current->client_name,
                        'client_type' => $current->client_type,
                        'service_category' => $current->serviceCategory->name ?? null,
                        'transaction_service_categories' => $current->transaction_service_categories,
                    ] : null,
                ];
            });

        $next = Queue::where('status', Queue::STATUS_WAITING)
            ->with(['serviceCategory'])
            ->orderByRaw("CASE WHEN client_type IN ('senior_citizen', 'high_priority') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($q) {
                return [
                    'queue_number' => $q->queue_number,
                    'client_name' => $q->client_name,
                    'client_type' => $q->client_type,
                    'service_category' => $q->serviceCategory->name ?? null,
                    'transaction_service_categories' => $q->transaction_service_categories,
                ];
            });

        return response()->json([
            'windows' => $windows,
            'next' => $next,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Show queue status page by queue number
     */
    public function showQueueByNumber($queue_number)
    {
        return Inertia::render('Public/QueueStatus', ['queue_number' => $queue_number]);
    }

    /**
     * API endpoint: Get data for a specific queue (position, status, ETA)
     */
    public function getQueueData($queue_number)
    {
        $queue = Queue::with(['serviceCategory', 'cashierWindow'])->where('queue_number', $queue_number)->first();

        if (!$queue) {
            return response()->json(['error' => 'Queue not found'], 404);
        }

        if (
            $queue->status === Queue::STATUS_COMPLETED
            && $queue->end_time
            && $queue->end_time->lte(now()->subMinutes(self::COMPLETED_QUEUE_LINK_EXPIRY_MINUTES))
        ) {
            return response()->json([
                'error' => 'This queue link has expired 5 minutes after completion.',
            ], 410);
        }

        $position = null;
        $eta = null;
        $estimatedServedAt = null;
        $waitingAhead = null;
        $activeCalledAhead = null;
        $queuesAhead = null;

        if ($queue->status === Queue::STATUS_WAITING) {
            $position = $this->resolveWeightedPosition($queue);

            if ($position) {
                $waitingAhead = max(0, $position - 1);
                $activeCalledAhead = Queue::query()
                    ->where('status', Queue::STATUS_CALLED)
                    ->count();
                $queuesAhead = $waitingAhead + $activeCalledAhead;

                $eta = $this->resolveEstimatedWaitMinutes($queue, $position);

                if ($eta !== null) {
                    $estimatedServedAt = now()->addMinutes($eta)->toIso8601String();
                }
            }
        }

        return response()->json([
            'queue_number' => $queue->queue_number,
            'status' => $queue->status,
            'client_name' => $queue->client_name,
            'client_number' => $queue->phone,
            'client_type' => $queue->client_type,
            'is_priority' => $queue->isPriorityClientType(),
            'service_category' => $queue->serviceCategory->name ?? null,
            'transaction_service_categories' => $queue->transaction_service_categories,
            'position' => $position,
            'waiting_ahead' => $waitingAhead,
            'active_called_ahead' => $activeCalledAhead,
            'queues_ahead' => $queuesAhead,
            'eta_minutes' => $eta,
            'estimated_served_at' => $estimatedServedAt,
            'created_at' => $queue->created_at?->toIso8601String(),
            'start_time' => $queue->start_time?->toIso8601String(),
            'cashier_window' => $queue->cashierWindow?->name,
        ]);
    }

    public function cancelQueue(Request $request, string $queue_number)
    {
        $queue = Queue::query()->where('queue_number', $queue_number)->first();

        if (!$queue) {
            return response()->json([
                'ok' => false,
                'message' => 'Queue not found.',
            ], 404);
        }

        if ($queue->status !== Queue::STATUS_WAITING) {
            return response()->json([
                'ok' => false,
                'message' => 'Only waiting queues can be cancelled.',
            ], 422);
        }

        $queue->status = Queue::STATUS_SKIPPED;
        $queue->cashier_window_id = null;
        $queue->start_time = null;
        $queue->end_time = now();
        $queue->is_reinstated = true;
        $queue->save();

        QueueLog::query()->create([
            'queue_id' => $queue->id,
            'action' => 'skipped',
            'performed_by' => null,
            'meta' => [
                'source' => 'public_mobile',
                'reason' => 'client_cancelled',
            ],
        ]);

        try {
            DisplayQueuesUpdated::dispatch('cancelled', $queue->id);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Queue cancelled successfully.',
        ]);
    }

    private function resolveWeightedPosition(Queue $targetQueue): ?int
    {
        $waitingQueues = Queue::query()
            ->where('status', Queue::STATUS_WAITING)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'client_type']);

        if ($waitingQueues->isEmpty()) {
            return null;
        }

        $priorityQueueIds = $waitingQueues
            ->filter(fn (Queue $queue) => in_array($queue->client_type, Queue::PRIORITY_CLIENT_TYPES, true))
            ->pluck('id')
            ->values()
            ->all();

        $regularQueueIds = $waitingQueues
            ->filter(fn (Queue $queue) => !in_array($queue->client_type, Queue::PRIORITY_CLIENT_TYPES, true))
            ->pluck('id')
            ->values()
            ->all();

        $counter = QueueCounter::query()
            ->whereDate('date', now()->toDateString())
            ->whereNull('service_category_id')
            ->first();

        $regularServedInCycle = (int) ($counter?->regular_served_in_cycle ?? 0);

        $scheduledOrder = [];

        while (!empty($priorityQueueIds) || !empty($regularQueueIds)) {
            if (!empty($priorityQueueIds) && $regularServedInCycle >= 2) {
                $scheduledOrder[] = array_shift($priorityQueueIds);
                $regularServedInCycle = 0;
                continue;
            }

            if (!empty($regularQueueIds)) {
                $scheduledOrder[] = array_shift($regularQueueIds);
                $regularServedInCycle = min(2, $regularServedInCycle + 1);
                continue;
            }

            $scheduledOrder[] = array_shift($priorityQueueIds);
            $regularServedInCycle = 0;
        }

        $index = array_search($targetQueue->id, $scheduledOrder, true);

        if ($index === false) {
            return null;
        }

        return $index + 1;
    }

    private function resolveEstimatedWaitMinutes(Queue $queue, int $position): int
    {
        $avgServiceSeconds = (int) ($queue->serviceCategory?->avg_service_seconds ?? 300);

        $activeCalledCount = Queue::query()
            ->where('status', Queue::STATUS_CALLED)
            ->count();

        $activeWindows = CashierWindow::query()
            ->where('active', true)
            ->whereNotNull('assigned_user_id')
            ->count();

        if ($activeWindows < 1) {
            $activeWindows = max(1, CashierWindow::query()->where('active', true)->count());
        }

        $queuesAhead = max(0, ($position - 1) + $activeCalledCount);
        $estimatedSeconds = ($queuesAhead * $avgServiceSeconds) / max(1, $activeWindows);

        return (int) ceil($estimatedSeconds / 60);
    }
}

