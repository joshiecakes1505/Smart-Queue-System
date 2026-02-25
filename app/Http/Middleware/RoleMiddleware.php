<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: 'role:admin' or 'role:cashier,frontdesk'
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = optional($user->role)->name;

        if (!$userRole) {
            abort(403);
        }

        if (!in_array($userRole, $roles, true)) {
            return match ($userRole) {
                'admin' => redirect()->route('admin.dashboard'),
                'frontdesk' => redirect()->route('frontdesk.queues.index'),
                'cashier' => redirect()->route('cashier.index'),
                default => abort(403),
            };
        }

        return $next($request);
    }
}
