<?php

namespace App\Services;

use App\Models\CashierWindow;
use App\Models\QueueLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Per-window transaction history, shared by the Cashier and Admin pages.
 *
 * Source of truth is queue_logs, NOT queues.cashier_window_id: skip() and
 * reinstate() both null the window on the ticket, so a finished ticket no
 * longer says which window handled it. Every `called` log, however, records
 * meta.window_id at the moment of the call, so one `called` log == one
 * transaction at that window, and the attribution stays correct even after
 * the ticket is skipped, reinstated, or later called by a different window.
 *
 * The outcome of each call is the first `completed`/`skipped` log for that
 * ticket at or after the call, so a ticket called twice is reported once per
 * call with its own result.
 */
class WindowTransactionService
{
    private const OUTCOME_ACTIONS = ['completed', 'skipped'];

    /**
     * @return array{rows: LengthAwarePaginator, summary: array}
     */
    public function build(?int $windowId, string $date, int $perPage = 20, int $page = 1): array
    {
        $calls = $this->fetchCalls($windowId, $date);
        $outcomes = $this->fetchOutcomes($calls->pluck('queue_id')->unique()->all());
        $windowNames = CashierWindow::query()->pluck('name', 'id');

        $rows = $calls->map(function (QueueLog $call) use ($outcomes, $windowNames) {
            $outcome = $this->resolveOutcome($call, $outcomes);
            $queue = $call->queue;
            $callWindowId = (int) ($call->meta['window_id'] ?? 0);

            return [
                'id' => $call->id,
                'queue_number' => $queue?->queue_number,
                'service_category' => $queue?->serviceCategory?->name,
                'client_name' => $queue?->client_name,
                'client_type' => $queue?->client_type,
                'window_id' => $callWindowId ?: null,
                'window_name' => $windowNames[$callWindowId] ?? null,
                'cashier' => $call->performer?->name,
                'called_at' => $call->created_at?->toIso8601String(),
                'finished_at' => $outcome?->created_at?->toIso8601String(),
                'outcome' => $outcome?->action ?? 'ongoing',
                // Plain timestamp subtraction: Carbon 3's diffInSeconds() is
                // signed, so argument order there silently yields negatives.
                'duration_seconds' => $outcome
                    ? max(0, $outcome->created_at->getTimestamp() - $call->created_at->getTimestamp())
                    : null,
            ];
        })->values();

        return [
            'rows' => $this->paginate($rows, $perPage, $page),
            'summary' => $this->summarise($rows),
        ];
    }

    private function fetchCalls(?int $windowId, string $date): Collection
    {
        $query = QueueLog::query()
            ->with(['queue.serviceCategory', 'performer'])
            ->where('action', 'called')
            ->whereDate('created_at', $date);

        if ($windowId) {
            $query->where('meta->window_id', $windowId);
        }

        // A day at one counter is tens of rows, so resolving outcomes and the
        // summary in PHP is cheaper than repeating the correlation in SQL.
        return $query->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    /**
     * @param  array<int>  $queueIds
     * @return Collection<int, Collection<int, QueueLog>>
     */
    private function fetchOutcomes(array $queueIds): Collection
    {
        if (empty($queueIds)) {
            return collect();
        }

        return QueueLog::query()
            ->whereIn('queue_id', $queueIds)
            ->whereIn('action', self::OUTCOME_ACTIONS)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('queue_id');
    }

    private function resolveOutcome(QueueLog $call, Collection $outcomes): ?QueueLog
    {
        return ($outcomes[$call->queue_id] ?? collect())
            ->first(fn (QueueLog $log) => $log->created_at >= $call->created_at);
    }

    private function summarise(Collection $rows): array
    {
        $completed = $rows->where('outcome', 'completed');
        $timed = $completed->whereNotNull('duration_seconds');
        $averageSeconds = $timed->count() > 0
            ? (int) round($timed->avg('duration_seconds'))
            : 0;

        return [
            'total' => $rows->count(),
            'completed' => $completed->count(),
            'skipped' => $rows->where('outcome', 'skipped')->count(),
            'ongoing' => $rows->where('outcome', 'ongoing')->count(),
            'average_service_seconds' => $averageSeconds,
            'average_service_minutes' => $averageSeconds > 0 ? round($averageSeconds / 60, 1) : 0,
        ];
    }

    private function paginate(Collection $rows, int $perPage, int $page): LengthAwarePaginator
    {
        $page = max(1, $page);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function resolveDate(?string $input): string
    {
        try {
            return $input ? Carbon::parse($input)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}
