<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashierWindow;
use App\Services\WindowTransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(private WindowTransactionService $transactions)
    {
        $this->middleware('auth:admin');
        $this->middleware('role:admin');
    }

    public function index(Request $request)
    {
        $windows = CashierWindow::query()->orderBy('name')->get(['id', 'name']);

        $requestedWindow = (int) $request->input('window_id', 0);
        $windowId = $windows->contains('id', $requestedWindow) ? $requestedWindow : null;

        $date = $this->transactions->resolveDate($request->input('date'));

        $result = $this->transactions->build($windowId, $date, 20, (int) $request->input('page', 1));

        return Inertia::render('Admin/Transactions/Index', [
            'windows' => $windows,
            'rows' => $result['rows'],
            'summary' => $result['summary'],
            'filters' => [
                'date' => $date,
                'window_id' => $windowId,
            ],
        ]);
    }
}
