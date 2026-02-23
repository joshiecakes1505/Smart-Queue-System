<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Welcome', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'roles' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'frontdesk', 'label' => 'Front Desk'],
                ['value' => 'cashier', 'label' => 'Cashier'],
            ],
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $guard = $request->authenticatedGuard();
        Auth::shouldUse($guard);

        $request->session()->regenerate();

        // Redirect based on user role
        $user = Auth::guard($guard)->user();
        $roleName = $user->role?->name;

        return match ($roleName) {
            'admin' => redirect()->route('admin.dashboard'),
            'frontdesk' => redirect()->route('frontdesk.queues.index'),
            'cashier' => redirect()->route('cashier.index'),
            default => redirect()->route('admin.dashboard'),
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $role = $request->input('role');

        if (in_array($role, ['admin', 'frontdesk', 'cashier', 'web'], true)) {
            Auth::guard($role)->logout();
        } else {
            foreach (['admin', 'frontdesk', 'cashier', 'web'] as $guard) {
                if (Auth::guard($guard)->check()) {
                    Auth::guard($guard)->logout();
                    break;
                }
            }
        }

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
