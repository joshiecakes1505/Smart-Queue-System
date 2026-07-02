<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SendTwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()->where('email', '=', $request->email)->first();

        if (! $user) {
            return back()->with('status', 'If the email address is registered, a verification code will be sent before the reset link.');
        }

        $user->forceFill([
            'two_factor_code' => rand(100000, 999999),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        session([
            'password_reset_email' => $user->email,
            'password_reset_2fa_verified' => false,
        ]);

        $user->notify(new SendTwoFactorCode('password reset'));

        return redirect()->route('password.two-factor.index');
    }
}
