<?php

namespace App\Services;

use App\Models\CashierWindow;
use App\Models\Queue;
use App\Models\QueueCounter;
use Illuminate\Database\Eloquent\Builder;

class QueueSchedulingService
{
    private const REGULARS_PER_CYCLE = 2;

    public function assignWindowForIncomingQueue(int $serviceCategoryId, QueueCounter $counter): ?int
    {
        $activeWindows = CashierWindow::query()
            ->where('active', true)
            ->whereNotNull('assigned_user_id')
            ->pluck('id')
            ->values()
            ->all();

        if (empty($activeWindows)) {
            $activeWindows = CashierWindow::query()
                ->where('active', true)
                ->pluck('id')
                ->values()
                ->all();
        }

        if (empty($activeWindows)) {
            return null;
        }

        $loads = Queue::query()
            ->selectRaw('cashier_window_id, COUNT(*) as total')
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED])
            ->whereIn('cashier_window_id', $activeWindows)
            ->groupBy('cashier_window_id')
            ->pluck('total', 'cashier_window_id');

        $minLoad = collect($activeWindows)
            ->map(fn (int $windowId) => (int) ($loads[$windowId] ?? 0))
            ->min();

        $leastLoaded = collect($activeWindows)
            ->filter(fn (int $windowId) => (int) ($loads[$windowId] ?? 0) === (int) $minLoad)
            ->values()
            ->all();

        $orderedCandidates = $this->rotateWindowOrder($leastLoaded, $counter->last_assigned_window_id);
        $selectedWindowId = $orderedCandidates[0] ?? $leastLoaded[0] ?? null;

        if ($selectedWindowId) {
            $counter->last_assigned_window_id = $selectedWindowId;
            $counter->save();
        }

        return $selectedWindowId;
    }

    public function selectNextQueueForWindow(int $windowId, ?int $serviceCategoryId, QueueCounter $counter): ?Queue
    {
        $windowScopedQuery = Queue::query()
            ->where('status', Queue::STATUS_WAITING)
            ->where('cashier_window_id', $windowId);

        if ($serviceCategoryId) {
            $windowScopedQuery->where('service_category_id', $serviceCategoryId);
        }

        $next = $this->pickWeightedQueue($windowScopedQuery, $counter);

        if ($next) {
            return $next;
        }

        $globalQuery = Queue::query()->where('status', Queue::STATUS_WAITING);

        if ($serviceCategoryId) {
            $globalQuery->where('service_category_id', $serviceCategoryId);
        }

        $next = $this->pickWeightedQueue($globalQuery, $counter);

        if ($next && (int) ($next->cashier_window_id ?? 0) !== $windowId) {
            $next->cashier_window_id = $windowId;
            $next->save();
        }

        return $next;
    }

    public function estimateWaitMinutes(Queue $queue): ?int
    {
        if ($queue->status !== Queue::STATUS_WAITING) {
            return null;
        }

        $queue->loadMissing('serviceCategory');

        $baseAheadQuery = Queue::query()
            ->where('status', Queue::STATUS_WAITING)
            ->where('service_category_id', $queue->service_category_id)
            ->where('created_at', '<', $queue->created_at);

        $regularAhead = (clone $baseAheadQuery)
            ->whereNotIn('client_type', Queue::PRIORITY_CLIENT_TYPES)
            ->count();

        $priorityAhead = (clone $baseAheadQuery)
            ->whereIn('client_type', Queue::PRIORITY_CLIENT_TYPES)
            ->count();

        $activeCalled = Queue::query()
            ->where('status', Queue::STATUS_CALLED)
            ->where('service_category_id', $queue->service_category_id)
            ->count();

        $effectiveAhead = $queue->isPriorityClientType()
            ? ($regularAhead * 0.6) + $priorityAhead
            : $regularAhead + ($priorityAhead * 1.25);

        $slotsAhead = max(0, $effectiveAhead + $activeCalled);

        $avgServiceSeconds = (int) ($queue->serviceCategory?->avg_service_seconds ?? 300);

        $activeWindows = CashierWindow::query()
            ->where('active', true)
            ->whereNotNull('assigned_user_id')
            ->count();

        if ($activeWindows < 1) {
            $activeWindows = max(1, CashierWindow::query()->where('active', true)->count());
        }

        $estimatedSeconds = ($slotsAhead * $avgServiceSeconds) / max(1, $activeWindows);

        return (int) ceil($estimatedSeconds / 60);
    }

    private function pickWeightedQueue(Builder $baseQuery, QueueCounter $counter): ?Queue
    {
        $priorityQueue = (clone $baseQuery)
            ->whereIn('client_type', Queue::PRIORITY_CLIENT_TYPES)
            ->orderBy('created_at', 'asc')
            ->first();

        $regularQueue = (clone $baseQuery)
            ->whereNotIn('client_type', Queue::PRIORITY_CLIENT_TYPES)
            ->orderBy('created_at', 'asc')
            ->first();

        if (! $priorityQueue && ! $regularQueue) {
            return null;
        }

        $regularServedInCycle = (int) ($counter->regular_served_in_cycle ?? 0);

        if ($priorityQueue && $regularServedInCycle >= self::REGULARS_PER_CYCLE) {
            $counter->regular_served_in_cycle = 0;
            $counter->save();

            return $priorityQueue;
        }

        if ($regularQueue) {
            $counter->regular_served_in_cycle = min(self::REGULARS_PER_CYCLE, $regularServedInCycle + 1);
            $counter->save();

            return $regularQueue;
        }

        $counter->regular_served_in_cycle = 0;
        $counter->save();

        return $priorityQueue;
    }

    /**
     * @param  array<int>  $candidateWindowIds
     * @return array<int>
     */
    private function rotateWindowOrder(array $candidateWindowIds, ?int $lastAssignedWindowId): array
    {
        sort($candidateWindowIds);

        if (!$lastAssignedWindowId || !in_array($lastAssignedWindowId, $candidateWindowIds, true)) {
            return $candidateWindowIds;
        }

        $lastIndex = array_search($lastAssignedWindowId, $candidateWindowIds, true);

        return array_merge(
            array_slice($candidateWindowIds, $lastIndex + 1),
            array_slice($candidateWindowIds, 0, $lastIndex + 1)
        );
    }
}
