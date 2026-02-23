<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CashierWindow;
use App\Models\Queue as QueueModel;
use Illuminate\Support\Facades\Auth;

class CashierController extends Controller
{
    protected QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->middleware('auth');
        $this->middleware('role:cashier');
        $this->queueService = $queueService;
    }

    public function index()
    {
        $userId = Auth::id();
        $window = CashierWindow::where('assigned_user_id', $userId)->first();

        $current = null;
        $next = collect();
        $recentLogs = collect();

        if ($window) {
            $current = QueueModel::with('serviceCategory')
                ->where('cashier_window_id', $window->id)
                ->where('status', QueueModel::STATUS_CALLED)
                ->orderBy('start_time', 'desc')
                ->first();
            
            $recentLogs = QueueModel::with('serviceCategory')
                ->where('cashier_window_id', $window->id)
                ->whereIn('status', [QueueModel::STATUS_COMPLETED, QueueModel::STATUS_SKIPPED])
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();
        }

        $next = QueueModel::with('serviceCategory')
            ->where('status', QueueModel::STATUS_WAITING)
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get();

        return Inertia::render('Cashier/Dashboard', [
            'window' => $window,
            'current' => $current,
            'next' => $next,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function callNext(Request $request)
    {
        $request->validate([
            'window_id' => ['required', 'integer'],
            'service_category_id' => ['nullable', 'integer'],
        ]);

        $windowId = (int) $request->input('window_id');
        $serviceCategoryId = $request->input('service_category_id') ? (int) $request->input('service_category_id') : null;
        $performedBy = $request->user()?->id;

        $next = $this->queueService->callNext($windowId, $serviceCategoryId, $performedBy);

        if (!$next) {
            return response()->json(['status' => 'empty']);
        }

        return response()->json(['status' => 'ok', 'queue' => $next]);
    }

    public function skip(Request $request, $queue)
    {
        $performedBy = $request->user()?->id;
        $res = $this->queueService->skip((int)$queue, $performedBy);
        return response()->json(['status' => $res ? 'ok' : 'not_found', 'queue' => $res]);
    }

    public function recall(Request $request, $queue)
    {
        $performedBy = $request->user()?->id;
        $res = $this->queueService->recall((int)$queue, $performedBy);
        return response()->json(['status' => $res ? 'ok' : 'not_found', 'queue' => $res]);
    }

    public function complete(Request $request, $queue)
    {
        $performedBy = $request->user()?->id;
        $res = $this->queueService->complete((int)$queue, $performedBy);
        return response()->json(['status' => $res ? 'ok' : 'not_found', 'queue' => $res]);
    }
}
