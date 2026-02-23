<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CashierWindow;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicQueueController extends Controller
{
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
                    ->where('status', Queue::STATUS_CALLED)
                    ->orderBy('start_time', 'desc')
                    ->first();

                return [
                    'id' => $w->id,
                    'name' => $w->name ?? 'Window ' . $w->id,
                    'assigned_user' => $w->assignedUser?->name,
                    'current' => $current ? [
                        'queue_number' => $current->queue_number,
                        'client_name' => $current->client_name,
                        'service_category' => $current->serviceCategory->name ?? null,
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
        $queue = Queue::where('queue_number', $queue_number)->first();

        if (!$queue) {
            return response()->json(['error' => 'Queue not found'], 404);
        }

        // Find position in waiting queue if status is waiting
        $position = null;
        if ($queue->status === Queue::STATUS_WAITING) {
            $position = Queue::where('status', Queue::STATUS_WAITING)
                ->where('service_category_id', $queue->service_category_id)
                ->where('created_at', '<', $queue->created_at)
                ->count() + 1;
        }

        $eta = $this->queueService->estimateWaitMinutes($queue);

        return response()->json([
            'queue_number' => $queue->queue_number,
            'status' => $queue->status,
            'client_name' => $queue->client_name,
            'client_number' => $queue->phone,
            'client_type' => $queue->client_type,
            'is_priority' => $queue->isPriorityClientType(),
            'service_category' => $queue->serviceCategory->name ?? null,
            'position' => $position,
            'eta_minutes' => $eta,
            'created_at' => $queue->created_at?->toIso8601String(),
            'start_time' => $queue->start_time?->toIso8601String(),
            'cashier_window' => $queue->cashierWindow?->name,
        ]);
    }
}

