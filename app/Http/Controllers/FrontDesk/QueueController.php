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
        $waitingQueues = \App\Models\Queue::with('serviceCategory')
            ->where('status', \App\Models\Queue::STATUS_WAITING)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $totalWaiting = \App\Models\Queue::where('status', \App\Models\Queue::STATUS_WAITING)->count();
        $totalServedToday = \App\Models\Queue::whereDate('created_at', today())
            ->where('status', \App\Models\Queue::STATUS_COMPLETED)
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
        $queue = $this->queueService->createQueue($request->validated());
        
        return redirect()->route('frontdesk.queues.print', $queue);
    }

    public function print(\App\Models\Queue $queue)
    {
        $queue->load('serviceCategory');
        
        return Inertia::render('FrontDesk/PrintTicket', [
            'queue' => $queue,
        ]);
    }
}

