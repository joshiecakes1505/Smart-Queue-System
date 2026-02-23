<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('role:admin');
    }

    public function daily(Request $request)
    {
        $dateInput = $request->input('date');
        $selectedDate = $dateInput ? Carbon::parse($dateInput)->toDateString() : now()->toDateString();

        $queuesQuery = Queue::query()
            ->with('serviceCategory')
            ->whereDate('created_at', $selectedDate);

        $queues = $queuesQuery->get();

        $total = $queues->count();
        $waiting = $queues->where('status', Queue::STATUS_WAITING)->count();
        $called = $queues->where('status', Queue::STATUS_CALLED)->count();
        $completed = $queues->where('status', Queue::STATUS_COMPLETED)->count();
        $skipped = $queues->where('status', Queue::STATUS_SKIPPED)->count();

        $completedWithTimes = $queues->filter(function (Queue $queue) {
            return $queue->status === Queue::STATUS_COMPLETED && $queue->start_time && $queue->end_time;
        });

        $averageServiceSeconds = $completedWithTimes->count() > 0
            ? (int) round($completedWithTimes->avg(fn (Queue $queue) => $queue->end_time->diffInSeconds($queue->start_time)))
            : 0;

        $statusBreakdown = [
            ['status' => Queue::STATUS_WAITING, 'count' => $waiting],
            ['status' => Queue::STATUS_CALLED, 'count' => $called],
            ['status' => Queue::STATUS_COMPLETED, 'count' => $completed],
            ['status' => Queue::STATUS_SKIPPED, 'count' => $skipped],
        ];

        $clientBreakdown = $queues
            ->groupBy(fn (Queue $queue) => $queue->client_type ?? 'unknown')
            ->map(fn ($group, $type) => [
                'client_type' => $type,
                'count' => $group->count(),
            ])
            ->values();

        $serviceCategoryBreakdown = $queues
            ->groupBy(fn (Queue $queue) => $queue->serviceCategory?->name ?? 'Uncategorized')
            ->map(fn ($group, $serviceName) => [
                'service_category' => $serviceName,
                'total' => $group->count(),
                'completed' => $group->where('status', Queue::STATUS_COMPLETED)->count(),
                'waiting' => $group->where('status', Queue::STATUS_WAITING)->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $hourlyData = collect(range(0, 23))->map(function (int $hour) use ($queues) {
            $hourCount = $queues->filter(function (Queue $queue) use ($hour) {
                return (int) $queue->created_at?->format('G') === $hour;
            })->count();

            return [
                'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'count' => $hourCount,
            ];
        });

        $metrics = [
            'selected_date' => $selectedDate,
            'total_queues' => $total,
            'waiting' => $waiting,
            'called' => $called,
            'completed' => $completed,
            'skipped' => $skipped,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'average_service_seconds' => $averageServiceSeconds,
            'average_service_minutes' => round($averageServiceSeconds / 60, 1),
            'status_breakdown' => $statusBreakdown,
            'client_breakdown' => $clientBreakdown,
            'service_category_breakdown' => $serviceCategoryBreakdown,
            'hourly_data' => $hourlyData,
        ];

        return Inertia::render('Admin/Reports/Daily', [
            'metrics' => $metrics,
        ]);
    }
}
