<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQueueRequest;
use App\Models\ServiceCategory;
use App\Services\QueueService;
use Inertia\Inertia;

class QueueController extends Controller
{
    protected QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->middleware('auth');
        $this->middleware('role:frontdesk');
        $this->queueService = $queueService;
    }

    public function index()
    {
        $categories = ServiceCategory::orderBy('name')->get();
        return Inertia::render('FrontDesk/CreateQueue', [
            'serviceCategories' => $categories,
        ]);
    }

    public function store(StoreQueueRequest $request)
    {
        $queue = $this->queueService->createQueue($request->validated());
        
        $categories = ServiceCategory::orderBy('name')->get();
        return Inertia::render('FrontDesk/CreateQueue', [
            'serviceCategories' => $categories,
            'queueNumber' => $queue->queue_number,
        ]);
    }
}

