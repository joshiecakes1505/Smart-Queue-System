<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('role:admin');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $level = $request->input('level');
        $status = $request->input('status', 'unresolved');

        $errors = ErrorLog::query()
            ->with(['user:id,name', 'resolvedBy:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                        ->orWhere('exception_class', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->when($level, fn ($query) => $query->where('level', $level))
            ->when($status === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
            ->when($status === 'unresolved', fn ($query) => $query->whereNull('resolved_at'))
            ->orderByDesc('last_occurred_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Monitoring/Index', [
            'errors' => $errors,
            'filters' => [
                'search' => $search,
                'level' => $level,
                'status' => $status,
            ],
            'summary' => [
                'unresolved_count' => ErrorLog::query()->whereNull('resolved_at')->count(),
                'total_count' => ErrorLog::query()->count(),
            ],
        ]);
    }

    public function resolve(ErrorLog $errorLog)
    {
        $errorLog->markResolved(auth('admin')->id());

        return back()->with('success', 'Error marked as resolved.');
    }

    public function unresolve(ErrorLog $errorLog)
    {
        $errorLog->markUnresolved();

        return back()->with('success', 'Error reopened.');
    }

    public function destroy(ErrorLog $errorLog)
    {
        $errorLog->delete();

        return back()->with('success', 'Error log entry removed.');
    }
}
