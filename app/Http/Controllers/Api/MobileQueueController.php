<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QueueService;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\URL;

class MobileQueueController extends Controller
{
    protected QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function services()
    {
        return response()->json(
        ServiceCategory::query()
            ->orderBy('name', 'asc')
            ->get([
                'id',
                'name',
                'prefix',
            ])
        );
    }

    public function store(Request $request)
    {
        $serviceCategoryIds = $this->normalizeServiceCategoryIds($request);

        $request->merge([
            'service_category_ids' => $serviceCategoryIds,
        ]);

        $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:255'],

            'client_type' => [
                'required',
                'in:student,parent,visitor,senior_citizen,high_priority',
            ],

            'service_category_ids' => ['required', 'array', 'min:1'],

            'service_category_ids.*' => [
                'integer',
                'exists:service_categories,id',
            ],
        ]);

        $categoriesById = ServiceCategory::query()
            ->orWhereIn('id', $validated['service_category_ids'])
            ->get(['id', 'name'])
            ->keyBy('id');

        $serviceCategoryNames = collect($validated['service_category_ids'])
            ->map(fn (int $id) => $categoriesById->get($id)?->name)
            ->filter()
            ->values()
            ->all();

        $primaryServiceCategoryId = (int) $validated['service_category_ids'][0];

        $queue = $this->queueService->createQueue([
            ...$validated,
            'service_category_id' => $primaryServiceCategoryId,
            'note' => json_encode([
                'service_category_ids' => $validated['service_category_ids'],
                'service_category_names' => $serviceCategoryNames,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $queue->load('serviceCategory');

        return response()->json([
            'status' => 'success',

            'queue' => [
                'id' => $queue->id,

                'queue_number' => $queue->queue_number,

                'tracking_url' => URL::signedRoute('public.queue.show', ['queue_number' => $queue->queue_number]),

                'client_name' => $queue->client_name,

                'client_type' => $queue->client_type,

                'service' => [
                    'id' => $queue->serviceCategory->id,
                    'name' => $queue->serviceCategory->name,
                    'prefix' => $queue->serviceCategory->prefix,
                ],

                'status' => $queue->status,

                'created_at' => $queue->created_at?->format(
                    'F d, Y h:i A'
                ),
            ],
        ]);
    }

    private function normalizeServiceCategoryIds(Request $request): array
    {
        $rawServiceCategoryIds = $request->input('service_category_id');

        if ($rawServiceCategoryIds === null || $rawServiceCategoryIds === '') {
            $rawServiceCategoryIds = $request->input('service_ids', []);
        }

        if (!is_array($rawServiceCategoryIds)) {
            $rawServiceCategoryIds = [$rawServiceCategoryIds];
        }

        return collect($rawServiceCategoryIds)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }
}
