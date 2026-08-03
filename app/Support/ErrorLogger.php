<?php

namespace App\Support;

use App\Models\ErrorLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Captures reported exceptions into the error_logs table for the admin
 * monitoring page, in addition to (not instead of) normal file logging.
 */
class ErrorLogger
{
    /**
     * Routine/expected exception types that aren't worth tracking as "errors".
     */
    private const IGNORED_TYPES = [
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        NotFoundHttpException::class,
        TokenMismatchException::class,
    ];

    public static function capture(Throwable $e): void
    {
        foreach (self::IGNORED_TYPES as $type) {
            if ($e instanceof $type) {
                return;
            }
        }

        try {
            $existing = ErrorLog::query()
                ->where('exception_class', get_class($e))
                ->where('file', $e->getFile())
                ->where('line', $e->getLine())
                ->whereNull('resolved_at')
                ->where('last_occurred_at', '>=', now()->subHour())
                ->first();

            if ($existing) {
                $existing->increment('occurrences');
                $existing->forceFill(['last_occurred_at' => now()])->save();

                return;
            }

            $isConsole = app()->runningInConsole();

            ErrorLog::create([
                'level' => 'error',
                'exception_class' => get_class($e),
                'message' => self::truncate($e->getMessage() ?: get_class($e), 2000),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => self::truncate($e->getTraceAsString(), 20000),
                'url' => $isConsole ? null : Request::fullUrl(),
                'method' => $isConsole ? null : Request::method(),
                'user_id' => $isConsole ? null : self::currentUserId(),
                'occurrences' => 1,
                'last_occurred_at' => now(),
            ]);
        } catch (Throwable $loggingFailure) {
            // Error *logging* must never itself throw; fall back to the file log.
            Log::error('Failed to persist error_logs entry: '.$loggingFailure->getMessage());
        }
    }

    private static function currentUserId(): ?int
    {
        foreach (['admin', 'frontdesk', 'cashier', 'web'] as $guard) {
            $id = Auth::guard($guard)->id();
            if ($id) {
                return $id;
            }
        }

        return null;
    }

    private static function truncate(string $value, int $limit): string
    {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
    }
}
