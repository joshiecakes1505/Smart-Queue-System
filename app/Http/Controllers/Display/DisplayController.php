<?php

namespace App\Http\Controllers\Display;

use App\Http\Controllers\Controller;
use App\Models\CashierWindow;
use App\Models\Queue;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayController extends Controller
{
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
                    ->where('status', Queue::STATUS_CALLED)
                    ->orderBy('start_time', 'desc')
                    ->first();

                return [
                    'id' => $window->id,
                    'name' => $window->name ?? 'Window ' . $window->id,
                    'assigned_user' => $window->assignedUser?->name,
                    'current' => $current ? [
                        'queue_number' => $current->queue_number,
                        'client_name' => $current->client_name,
                        'service_category' => $current->serviceCategory->name ?? null,
                    ] : null,
                ];
            });

        $nextQueues = Queue::where('status', Queue::STATUS_WAITING)
            ->with(['serviceCategory'])
            ->orderByRaw("CASE WHEN client_type IN ('senior_citizen', 'high_priority') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'asc')
            ->limit(5)
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
            'next_queues' => $nextQueues,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

