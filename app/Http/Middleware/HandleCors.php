<?php

namespace Fruitcake\Cors;

use Closure;
use Illuminate\Http\Request;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
