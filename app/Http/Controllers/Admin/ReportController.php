<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    private const SUPPORTED_PERIODS = ['daily', 'weekly', 'monthly', 'yearly'];

    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('role:admin');
    }

    public function daily(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate($request->input('date'));
        $selectedPeriod = $this->resolveSelectedPeriod($request->input('period'));

        $metrics = $this->buildReportMetrics($selectedDate, $selectedPeriod);

        return Inertia::render('Admin/Reports/Daily', [
            'metrics' => $metrics,
        ]);
    }

    public function dailyPrint(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate($request->input('date'));
        $selectedPeriod = $this->resolveSelectedPeriod($request->input('period'));

        return response()->view('admin.reports.daily-print', [
            'metrics' => $this->buildReportMetrics($selectedDate, $selectedPeriod),
        ]);
    }

    private function buildReportMetrics(string $selectedDate, string $selectedPeriod): array
    {
        $anchorDate = Carbon::parse($selectedDate);
        [$rangeStart, $rangeEnd, $periodLabel] = $this->resolvePeriodRange($anchorDate, $selectedPeriod);

        $queuesQuery = Queue::query()
            ->with('serviceCategory')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd]);

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

        $reportStartHour = 7;
        $reportEndHour = 16;

        $hourlyData = collect(range($reportStartHour, $reportEndHour))->map(function (int $hour) use ($queues) {
            $hourCount = $queues->filter(function (Queue $queue) use ($hour) {
                return (int) $queue->created_at?->format('G') === $hour;
            })->count();

            return [
                'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'count' => $hourCount,
            ];
        });

        return [
            'selected_date' => $selectedDate,
            'selected_period' => $selectedPeriod,
            'period_label' => $periodLabel,
            'available_periods' => self::SUPPORTED_PERIODS,
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
    }

    private function resolveSelectedDate(?string $dateInput): string
    {
        return $dateInput ? Carbon::parse($dateInput)->toDateString() : now()->toDateString();
    }

    private function resolveSelectedPeriod(?string $periodInput): string
    {
        $period = strtolower((string) $periodInput);
        return in_array($period, self::SUPPORTED_PERIODS, true) ? $period : 'daily';
    }

    private function resolvePeriodRange(Carbon $anchorDate, string $selectedPeriod): array
    {
        if ($selectedPeriod === 'weekly') {
            $start = $anchorDate->copy()->startOfWeek();
            $end = $anchorDate->copy()->endOfWeek();

            return [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
                sprintf('Weekly (%s to %s)', $start->toDateString(), $end->toDateString()),
            ];
        }

        if ($selectedPeriod === 'monthly') {
            $start = $anchorDate->copy()->startOfMonth();
            $end = $anchorDate->copy()->endOfMonth();

            return [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
                sprintf('Monthly (%s)', $start->format('F Y')),
            ];
        }

        if ($selectedPeriod === 'yearly') {
            $start = $anchorDate->copy()->startOfYear();
            $end = $anchorDate->copy()->endOfYear();

            return [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
                sprintf('Yearly (%s)', $start->format('Y')),
            ];
        }

        return [
            $anchorDate->copy()->startOfDay(),
            $anchorDate->copy()->endOfDay(),
            sprintf('Daily (%s)', $anchorDate->toDateString()),
        ];
    }
}
