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

class PasswordResetTwoFactorController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/PasswordResetTwoFactor', [
            'status' => session('status'),
            'email' => $email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'two_factor_code' => ['required', 'integer'],
        ]);

        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $user = User::query()->where('email', '=', $email)->first();

        if (! $user) {
            return redirect()->route('password.request')->with('status', 'We could not verify that request. Please try again.');
        }

        if (
            (int) $request->two_factor_code === (int) $user->two_factor_code
            && $user->two_factor_expires_at
            && now()->lessThanOrEqualTo($user->two_factor_expires_at)
        ) {
            $user->forceFill([
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ])->save();

            $request->session()->put('password_reset_2fa_verified', true);

            $status = Password::sendResetLink([
                'email' => $email,
            ]);

            $request->session()->forget(['password_reset_email', 'password_reset_2fa_verified']);

            return redirect()->route('password.request')->with('status', __($status));
        }

        return back()->withErrors([
            'two_factor_code' => 'The provided verification code is invalid or has expired. Please try again.',
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $user = User::query()->where('email', '=', $email)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->forceFill([
            'two_factor_code' => rand(100000, 999999),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        $user->notify(new SendTwoFactorCode('password reset'));

        return back()->with('status', 'A new verification code has been sent to your email.');
    }
}