<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\CashierWindow;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $users = User::with('role')->orderBy('name')->get();

        $cashiers = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'cashier'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $cashierWindows = CashierWindow::with('assignedUser')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'cashiers' => $cashiers,
            'cashierWindows' => $cashierWindows,
        ]);
    }

    public function assignCashier(Request $request, CashierWindow $cashierWindow)
    {
        $validated = $request->validate([
            'cashier_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $cashierUserId = $validated['cashier_user_id'] ?? null;

        if ($cashierUserId !== null) {
            $isCashier = User::query()
                ->whereKey($cashierUserId)
                ->whereHas('role', fn ($q) => $q->where('name', 'cashier'))
                ->exists();

            if (! $isCashier) {
                return back()->withErrors([
                    'cashier_user_id' => 'Selected user is not a cashier.',
                ]);
            }
        }

        DB::transaction(function () use ($cashierUserId, $cashierWindow) {
            if ($cashierUserId !== null) {
                CashierWindow::where('assigned_user_id', $cashierUserId)
                    ->update(['assigned_user_id' => null]);
            }

            $cashierWindow->assigned_user_id = $cashierUserId;
            $cashierWindow->save();
        });

        return back()->with('success', 'Cashier assignment updated.');
    }

    public function create()
    {
        $roles = Role::query()
            ->whereIn('name', ['admin', 'cashier', 'frontdesk'])
            ->orderByRaw("CASE name WHEN 'admin' THEN 1 WHEN 'cashier' THEN 2 WHEN 'frontdesk' THEN 3 ELSE 4 END")
            ->get(['id', 'name']);

        return Inertia::render('Admin/Users/Create', ['roles' => $roles]);
    }

    public function store(StoreUserRequest $request)
    {
        User::create(array_merge(
            $request->validated(),
            ['password' => Hash::make($request->password)]
        ));
        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $roles = \App\Models\Role::pluck('name', 'id');
        return Inertia::render('Admin/Users/Edit', ['user' => $user, 'roles' => $roles]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
