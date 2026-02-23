<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? ['web', 'admin', 'frontdesk', 'cashier'] : $guards;

        foreach ($guards as $guard) {
            if (!Auth::guard($guard)->check()) {
                continue;
            }

            $roleName = Auth::guard($guard)->user()?->role?->name;

            return match ($roleName) {
                'admin' => redirect()->route('admin.dashboard'),
                'frontdesk' => redirect()->route('frontdesk.queues.index'),
                'cashier' => redirect()->route('cashier.index'),
                default => redirect()->route('landing'),
            };
        }

        return $next($request);
    }
}
