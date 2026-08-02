<?php

namespace App\Services;

use App\Models\CashierWindow;
use App\Models\Queue;
use App\Models\QueueCounter;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Builder;

class QueueSchedulingService
{
    private const DEFAULT_REGULARS_PER_CYCLE = 1;

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
        $regularsPerCycle = $this->resolveRegularsPerCycle($serviceCategoryId);

        $windowScopedQuery = Queue::query()
            ->where('status', Queue::STATUS_WAITING)
            ->where('cashier_window_id', $windowId);

        if ($serviceCategoryId) {
            $windowScopedQuery->where('service_category_id', $serviceCategoryId);
        }

        $next = $this->pickWeightedQueue($windowScopedQuery, $counter, $regularsPerCycle);

        if ($next) {
            return $next;
        }

        $globalQuery = Queue::query()->where('status', Queue::STATUS_WAITING);

        if ($serviceCategoryId) {
            $globalQuery->where('service_category_id', $serviceCategoryId);
        }

        $next = $this->pickWeightedQueue($globalQuery, $counter, $regularsPerCycle);

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

    private function pickWeightedQueue(Builder $baseQuery, QueueCounter $counter, int $regularsPerCycle): ?Queue
    {
        $priorityQueue = (clone $baseQuery)
            ->whereIn('client_type', Queue::PRIORITY_CLIENT_TYPES)
            ->orderBy('created_at', 'asc')
            ->first();

        $regularQueue = (clone $baseQuery)
            ->whereNotIn('client_type', Queue::PRIORITY_CLIENT_TYPES)
            ->orderBy('created_at', 'asc')
            ->first();

        $regularServedInCycle = (int) ($counter->regular_served_in_cycle ?? 0);

        $decision = $this->decideNextType($priorityQueue !== null, $regularQueue !== null, $regularServedInCycle, $regularsPerCycle);

        if ($decision === null) {
            return null;
        }

        $counter->regular_served_in_cycle = $this->nextCycleValue($decision, $regularsPerCycle, $regularServedInCycle);
        $counter->save();

        return $decision === 'priority' ? $priorityQueue : $regularQueue;
    }

    /**
     * The single source of truth for "priority or regular next", shared by the
     * live scheduler (pickWeightedQueue) and the read-only Display preview
     * (previewUpcomingQueues) so both always agree on the same call order.
     */
    private function decideNextType(bool $hasPriority, bool $hasRegular, int $regularServedInCycle, int $regularsPerCycle): ?string
    {
        if (!$hasPriority && !$hasRegular) {
            return null;
        }

        if ($hasPriority && ($regularsPerCycle === 0 || $regularServedInCycle >= $regularsPerCycle)) {
            return 'priority';
        }

        if ($hasRegular) {
            return 'regular';
        }

        return 'priority';
    }

    private function nextCycleValue(string $decision, int $regularsPerCycle, int $regularServedInCycle): int
    {
        if ($decision === 'priority') {
            return 0;
        }

        return $regularsPerCycle > 0
            ? min($regularsPerCycle, $regularServedInCycle + 1)
            : 0;
    }

    /**
     * Read-only preview of the next N queues to be called, using the exact
     * same window-scoping and priority/regular cycling rules as callNext(),
     * simulated in memory (no counter or cashier_window_id writes) so the
     * Display board's "Up Next" always matches what a cashier will actually call.
     */
    public function previewUpcomingQueues(int $limit = 6): array
    {
        $activeWindowIds = CashierWindow::query()
            ->where('active', true)
            ->whereNotNull('assigned_user_id')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (empty($activeWindowIds)) {
            return [];
        }

        // A window already serving someone (status=called) won't call again
        // until that customer is done, at an unknowable future time — so it
        // can't contribute a "next" pick right now. Only currently-free
        // windows can.
        $busyWindowIds = Queue::query()
            ->where('status', Queue::STATUS_CALLED)
            ->whereIn('cashier_window_id', $activeWindowIds)
            ->pluck('cashier_window_id')
            ->all();

        $freeWindowIds = array_values(array_diff($activeWindowIds, $busyWindowIds));

        // Actual call-time usage never scopes by service category (the Cashier
        // dashboard always posts window_id only), so the preview mirrors that.
        $regularsPerCycle = $this->resolveRegularsPerCycle(null);

        $regularServedInCycle = (int) (QueueCounter::query()
            ->whereDate('date', now()->toDateString())
            ->whereNull('service_category_id')
            ->value('regular_served_in_cycle') ?? 0);

        $pool = Queue::query()
            ->where('status', Queue::STATUS_WAITING)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'client_type', 'cashier_window_id', 'created_at'])
            ->all();

        $consumed = [];
        $orderedIds = [];

        // Phase 1: exactly one pick per currently-free window. This is the
        // part that must be exact — only a free window can call someone
        // right now, so these are the entries "Up Next" is allowed to match
        // against a real cashier (window 1, window 2, ...).
        foreach ($freeWindowIds as $windowId) {
            if (count($orderedIds) >= $limit) {
                break;
            }

            $pickedId = $this->previewPickForWindow($pool, $consumed, (int) $windowId, $regularsPerCycle, $regularServedInCycle);

            if ($pickedId !== null) {
                $orderedIds[] = $pickedId;
                $consumed[$pickedId] = true;
            }
        }

        // Phase 2: fill the rest of "Next in Queue" as a general priority/
        // regular-cycle projection over whoever is left. We can't say which
        // window calls these (a window only frees up at an unknowable time),
        // so this is informational ordering, not a per-window guarantee.
        while (count($orderedIds) < $limit) {
            $available = array_filter($pool, fn (Queue $q) => !isset($consumed[$q->id]));

            $pickedId = $this->pickFromCandidates($available, $regularsPerCycle, $regularServedInCycle);

            if ($pickedId === null) {
                break;
            }

            $orderedIds[] = $pickedId;
            $consumed[$pickedId] = true;
        }

        if (empty($orderedIds)) {
            return [];
        }

        $queuesById = Queue::with('serviceCategory')->whereIn('id', $orderedIds)->get()->keyBy('id');

        return collect($orderedIds)->map(fn ($id) => $queuesById[$id] ?? null)->filter()->values()->all();
    }

    /**
     * @param  array<int, Queue>  $pool
     * @param  array<int, bool>  $consumed
     */
    private function previewPickForWindow(array $pool, array &$consumed, int $windowId, int $regularsPerCycle, int &$regularServedInCycle): ?int
    {
        $available = array_filter($pool, fn (Queue $q) => !isset($consumed[$q->id]));

        $windowScoped = array_filter($available, fn (Queue $q) => (int) $q->cashier_window_id === $windowId);

        $pickedId = $this->pickFromCandidates($windowScoped, $regularsPerCycle, $regularServedInCycle);

        if ($pickedId !== null) {
            return $pickedId;
        }

        return $this->pickFromCandidates($available, $regularsPerCycle, $regularServedInCycle);
    }

    /**
     * @param  array<int, Queue>  $candidates
     */
    private function pickFromCandidates(array $candidates, int $regularsPerCycle, int &$regularServedInCycle): ?int
    {
        $priority = null;
        $regular = null;

        foreach ($candidates as $candidate) {
            if (in_array($candidate->client_type, Queue::PRIORITY_CLIENT_TYPES, true)) {
                if ($priority === null || $candidate->created_at->lt($priority->created_at)) {
                    $priority = $candidate;
                }
            } elseif ($regular === null || $candidate->created_at->lt($regular->created_at)) {
                $regular = $candidate;
            }
        }

        $decision = $this->decideNextType($priority !== null, $regular !== null, $regularServedInCycle, $regularsPerCycle);

        if ($decision === null) {
            return null;
        }

        $regularServedInCycle = $this->nextCycleValue($decision, $regularsPerCycle, $regularServedInCycle);

        return $decision === 'priority' ? $priority->id : $regular->id;
    }

    private function resolveRegularsPerCycle(?int $serviceCategoryId): int
    {
        if (! $serviceCategoryId) {
            return self::DEFAULT_REGULARS_PER_CYCLE;
        }

        $configuredValue = ServiceCategory::query()
            ->whereKey($serviceCategoryId)
            ->value('regulars_per_priority_cycle');

        if ($configuredValue === null) {
            return self::DEFAULT_REGULARS_PER_CYCLE;
        }

        return max(0, (int) $configuredValue);
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
