<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQueueRequest;
use App\Models\Queue;
use App\Services\PrintService;
use App\Models\ServiceCategory;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class QueueController extends Controller
{
    protected QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->middleware('auth:frontdesk');
        $this->middleware('role:frontdesk');
        $this->queueService = $queueService;
    }

    public function index()
    {
        $categories = ServiceCategory::query()->orderBy('name', 'asc')->get();
        $waitingQueues = Queue::query()->with('serviceCategory')
            ->where('status', Queue::STATUS_WAITING)
            ->orderByRaw("CASE WHEN client_type IN ('senior_citizen', 'high_priority') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (Queue $queue) {
                return [
                    ...$queue->toArray(),
                    'is_priority' => $queue->isPriorityClientType(),
                    'estimated_wait_minutes' => $this->queueService->estimateWaitMinutes($queue),
                ];
            });
        
        $totalWaiting = Queue::query()->where('status', Queue::STATUS_WAITING)->count();
        $totalServedToday = Queue::query()->whereDate('end_time', '=', today(), 'and')
            ->where('status', Queue::STATUS_COMPLETED)
            ->count();
        
        return Inertia::render('FrontDesk/Dashboard', [
            'serviceCategories' => $categories,
            'waitingQueues' => $waitingQueues,
            'totalWaiting' => $totalWaiting,
            'totalServedToday' => $totalServedToday,
        ]);
    }

    public function store(StoreQueueRequest $request)
    {
        $validated = $request->validated();

        $serviceCategoryIds = collect($validated['service_category_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($serviceCategoryIds->isEmpty() && !empty($validated['service_category_id'])) {
            $serviceCategoryIds = collect([(int) $validated['service_category_id']]);
        }

        if ($serviceCategoryIds->isEmpty()) {
            return back()->withErrors([
                'service_category_id' => 'Please select at least one service category.',
            ]);
        }

        $categoriesById = ServiceCategory::query()
            ->whereIn('id', $serviceCategoryIds->all(), 'and', false)
            ->get(['id', 'name'])
            ->keyBy('id');

        $selectedCategoryNames = $serviceCategoryIds
            ->map(fn (int $id) => $categoriesById->get($id)?->name)
            ->filter()
            ->values()
            ->all();

        $primaryServiceCategoryId = (int) $serviceCategoryIds->first();

        $notePayload = [
            'service_category_ids' => $serviceCategoryIds->all(),
            'service_category_names' => $selectedCategoryNames,
            'client_note' => $validated['note'] ?? null,
        ];

        $queue = $this->queueService->createQueue([
            ...$validated,
            'service_category_id' => $primaryServiceCategoryId,
            'note' => json_encode($notePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return redirect()
            ->route('frontdesk.queues.print', $queue)
            ->with([
                'queueNumber' => $queue->queue_number,
            ]);
    }

    public function print(\App\Models\Queue $queue)
    {
        $queue->load('serviceCategory');
        
        return Inertia::render('FrontDesk/PrintTicket', [
            'queue' => $queue,
        ]);
    }

    public function printReceipt(Queue $queue, PrintService $printService): JsonResponse
    {
        $result = $printService->printQueueReceipt($queue);

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], $result['status'] ?? 500);
        }

        return response()->json([
            'ok' => true,
            'message' => $result['message'] ?? 'Receipt printed successfully',
        ]);
    }
}

