<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierWindow;
use App\Services\WindowTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(private WindowTransactionService $transactions)
    {
        $this->middleware('auth:cashier');
        $this->middleware('role:cashier');
    }

    public function index(Request $request)
    {
        // A cashier only ever sees their own counter's history.
        $window = CashierWindow::query()->where('assigned_user_id', Auth::id())->first();
        $date = $this->transactions->resolveDate($request->input('date'));

        $result = $window
            ? $this->transactions->build($window->id, $date, 20, (int) $request->input('page', 1))
            : ['rows' => null, 'summary' => null];

        return Inertia::render('Cashier/Transactions', [
            'window' => $window,
            'rows' => $result['rows'],
            'summary' => $result['summary'],
            'filters' => ['date' => $date],
        ]);
    }
}
