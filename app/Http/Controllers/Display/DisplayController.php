<?php

namespace App\Http\Controllers\Display;

use App\Http\Controllers\Controller;
use App\Models\CashierWindow;
use App\Models\Queue;
use App\Services\QueueSchedulingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayController extends Controller
{
    public function __construct(private readonly QueueSchedulingService $scheduler)
    {
    }

    public function index()
    {
        return Inertia::render('Display/Board');
    }

    public function data()
    {
        $windows = CashierWindow::with(['assignedUser'])
            ->where('active', true)
            ->get()
            ->map(function ($window) {
                $current = Queue::where('cashier_window_id', $window->id)
                    ->with('serviceCategory')
                    ->where('status', Queue::STATUS_CALLED)
                    ->orderBy('start_time', 'desc')
                    ->first();

                return [
                    'id' => $window->id,
                    'name' => $window->name ?? 'Window ' . $window->id,
                    'assigned_user' => $window->assignedUser?->name,
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

        $nextQueues = collect($this->scheduler->previewUpcomingQueues(5))
            ->map(function ($q) {
                return [
                    'queue_number' => $q->queue_number,
                    'client_name' => $q->client_name,
                    'client_type' => $q->client_type,
                    'service_category' => $q->serviceCategory->name ?? null,
                    'transaction_service_categories' => $q->transaction_service_categories,
                ];
            });

        // Same "pending auto-reinstatement" set as the Cashier dashboard:
        // skipped and still within the automatic reinstatement attempt limit.
        $reinstatedQueues = Queue::where('status', Queue::STATUS_SKIPPED)
            ->where('skip_count', '<=', (int) config('ticketing.max_reinstatements', 2))
            ->where('is_reinstated', false)
            ->with('serviceCategory')
            ->orderBy('updated_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($q) {
                return [
                    'queue_number' => $q->queue_number,
                    'client_type' => $q->client_type,
                    'service_category' => $q->serviceCategory->name ?? null,
                    'transaction_service_categories' => $q->transaction_service_categories,
                ];
            });

        return response()->json([
            'windows' => $windows,
            'next_queues' => $nextQueues,
            'reinstated_queues' => $reinstatedQueues,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

