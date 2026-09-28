<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashierWindow;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashierWindowController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('role:admin');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('cashier_windows', 'name')],
            'active' => ['sometimes', 'boolean'],
        ]);

        CashierWindow::create([
            'name' => $data['name'],
            'active' => $data['active'] ?? true,
        ]);

        return back()->with('success', 'Cashier window added.');
    }

    public function update(Request $request, CashierWindow $cashierWindow)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60', Rule::unique('cashier_windows', 'name')->ignore($cashierWindow->id)],
            'active' => ['sometimes', 'boolean'],
        ]);

        // Closing a window that is mid-transaction would strand that client.
        if (array_key_exists('active', $data) && !$data['active'] && $this->hasLiveQueues($cashierWindow)) {
            return back()->withErrors([
                'active' => 'Finish or skip the queues at this window before closing it.',
            ]);
        }

        $cashierWindow->fill($data)->save();

        return back()->with('success', 'Cashier window updated.');
    }

    public function destroy(CashierWindow $cashierWindow)
    {
        if ($cashierWindow->assigned_user_id) {
            return back()->withErrors([
                'window' => 'Unassign the cashier from this window before deleting it.',
            ]);
        }

        if ($this->hasLiveQueues($cashierWindow)) {
            return back()->withErrors([
                'window' => 'This window still has waiting or in-progress queues. Clear them first.',
            ]);
        }

        // Past transactions are keyed off queue_logs.meta.window_id, so the
        // reports and per-window history survive the row being removed.
        $cashierWindow->delete();

        return back()->with('success', 'Cashier window deleted.');
    }

    private function hasLiveQueues(CashierWindow $window): bool
    {
        return Queue::query()
            ->where('cashier_window_id', $window->id)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_CALLED])
            ->exists();
    }
}
