<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\AccountCreatedMail;
use App\Models\CashierWindow;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    private const DEFAULT_PASSWORD = 'BECQueue@2026';

    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $users = User::withTrashed()->with('role')->orderBy('name')->get();

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
            'authUserId' => auth('admin')->id(),
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
                CashierWindow::query()->where('assigned_user_id', '=', $cashierUserId)
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
            ->where(function ($query) {
                $query->where('name', '=', 'admin')
                    ->orWhere('name', '=', 'cashier')
                    ->orWhere('name', '=', 'frontdesk');
            })
            ->orderByRaw("CASE name WHEN 'admin' THEN 1 WHEN 'cashier' THEN 2 WHEN 'frontdesk' THEN 3 ELSE 4 END")
            ->get(['id', 'name']);

        return Inertia::render('Admin/Users/Create', ['roles' => $roles]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create(array_merge(
            $request->validated(),
            ['password' => Hash::make(self::DEFAULT_PASSWORD)]
        ));

        $this->sendAccountCreatedEmail($user);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $roles = Role::query()
            ->where(function ($query) {
                $query->where('name', '=', 'admin')
                    ->orWhere('name', '=', 'cashier')
                    ->orWhere('name', '=', 'frontdesk');
            })
            ->orderByRaw("CASE name WHEN 'admin' THEN 1 WHEN 'cashier' THEN 2 WHEN 'frontdesk' THEN 3 ELSE 4 END")
            ->get(['id', 'name']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only(['id', 'name', 'email', 'role_id']),
            'roles' => $roles,
            'authUserId' => auth('admin')->id(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function resetPassword(User $user)
    {
        abort_if(auth('admin')->id() === $user->id, 403, 'You cannot reset your own password from this panel.');

        $user->update([
            'password' => Hash::make(self::DEFAULT_PASSWORD),
        ]);

        $this->sendAccountCreatedEmail($user);

        return redirect()->route('admin.users.edit', $user)->with('success', 'Password reset to the default value and email sent.');
    }

    public function enable(User $user)
    {
        if (auth('admin')->id() === $user->id) {
            return back()->withErrors([
                'user' => 'You cannot change your own account status from this panel.',
            ]);
        }

        $user->enable();

        return redirect()->route('admin.users.index')->with('success', 'User enabled.');
    }

    public function destroy(User $user)
    {
        if (auth('admin')->id() === $user->id) {
            return back()->withErrors([
                'user' => 'You cannot disable your own account from this panel.',
            ]);
        }

        if ($user->isSoleActiveAdmin()) {
            return back()->withErrors([
                'user' => 'Cannot disable the last active admin account.',
            ]);
        }

        $user->disable();

        return redirect()->route('admin.users.index')->with('success', 'User disabled.');
    }

    public function archive(User $user)
    {
        if (auth('admin')->id() === $user->id) {
            return back()->withErrors([
                'user' => 'You cannot archive your own account from this panel.',
            ]);
        }

        if ($user->isSoleActiveAdmin()) {
            return back()->withErrors([
                'user' => 'Cannot archive the last active admin account.',
            ]);
        }

        if (!$user->disabled_at) {
            $user->disable();
        }

        $user->archive();

        return redirect()->route('admin.users.index')->with('success', 'User archived.');
    }

    public function unarchive(User $user)
    {
        $user->unarchive();

        return redirect()->route('admin.users.index')->with('success', 'User restored from archive.');
    }

    public function softDeleteNow(User $user)
    {
        if (auth('admin')->id() === $user->id) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account from this panel.',
            ]);
        }

        if ($user->isSoleActiveAdmin()) {
            return back()->withErrors([
                'user' => 'Cannot delete the last active admin account.',
            ]);
        }

        if (!$user->disabled_at) {
            $user->disable();
        }

        if (!$user->archived_at) {
            $user->archive();
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function restoreDeleted(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('admin.users.index')->with('success', 'User restored.');
    }

    private function sendAccountCreatedEmail(User $user): void
    {
        Mail::to($user->email)->send(new AccountCreatedMail(
            email: $user->email,
            password: self::DEFAULT_PASSWORD,
        ));
    }
}
