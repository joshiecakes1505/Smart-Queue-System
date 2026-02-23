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

        if (!$userRole || !in_array($userRole, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
