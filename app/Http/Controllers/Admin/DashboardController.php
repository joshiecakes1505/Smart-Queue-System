<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\User;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $today = Carbon::today();

        // Key metrics
        $totalQueuestoday = Queue::whereDate('created_at', $today)->count();
        $totalCompletedToday = Queue::whereDate('created_at', $today)
            ->where('status', Queue::STATUS_COMPLETED)
            ->count();
        $totalWaiting = Queue::where('status', Queue::STATUS_WAITING)->count();
        $totalUsers = User::count();

        // Average service time
        $avgServiceTime = Queue::whereDate('created_at', $today)
            ->whereNotNull('end_time')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_seconds'))
            ->first()
            ?->avg_seconds ?? 0;

        // Busiest hour
        $busiestHour = Queue::whereDate('created_at', $today)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('count', 'desc')
            ->first();

        return Inertia::render('Admin/Dashboard', [
            'totalQueuestoday' => $totalQueuestoday,
            'totalCompletedToday' => $totalCompletedToday,
            'totalWaitingNow' => $totalWaiting,
            'totalUsers' => $totalUsers,
            'avgServiceMinutes' => round($avgServiceTime / 60, 1),
            'busiestHour' => $busiestHour?->hour,
            'busiestHourCount' => $busiestHour?->count ?? 0,
        ]);
    }
}
