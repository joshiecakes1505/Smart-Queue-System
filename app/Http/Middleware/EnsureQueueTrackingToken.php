<?php

namespace App\Http\Middleware;

use App\Models\Queue;
use Closure;
use Illuminate\Http\Request;

class EnsureQueueTrackingToken
{
    /**
     * queue_number is sequential and guessable, so status/cancel requests
     * must also present the per-queue tracking_token before we act on them.
     */
    public function handle(Request $request, Closure $next)
    {
        $queueNumber = $request->route('queue_number');
        $token = (string) $request->input('token', '');

        $trackingToken = (string) Queue::query()
            ->where('queue_number', $queueNumber)
            ->value('tracking_token');

        if ($trackingToken === '' || $token === '' || !hash_equals($trackingToken, $token)) {
            return response()->json([
                'error' => 'Invalid or missing tracking token.',
            ], 403);
        }

        return $next($request);
    }
}
