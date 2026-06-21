<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QueueService;
use App\Models\ServiceCategory;

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
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'prefix',
            ])
        );
    }

    public function store(Request $request)
    {
         $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:255'],

            'client_type' => [
                'required',
                'in:student,parent,visitor,senior_citizen,high_priority',
            ],

            'service_category_id' => [
                'required',
                'exists:service_categories,id',
            ],

            'note' => ['nullable', 'string'],
        ]);

        $queue = $this->queueService->createQueue($validated);

        $queue->load('serviceCategory');

        return response()->json([
            'status' => 'success',

            'queue' => [
                'id' => $queue->id,

                'queue_number' => $queue->queue_number,

                'tracking_token' => $queue->tracking_token,

                'tracking_url' => url(
                    '/track/' . $queue->tracking_token
                ),

                'client_name' => $queue->client_name,

                'client_type' => $queue->client_type,

                'service' => $queue->serviceCategory?->name,

                'created_at' => $queue->created_at?->format(
                    'F d, Y h:i A'
                ),
            ],
        ]);
    }
}
