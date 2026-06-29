<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Notifications\SendTwoFactorCode;  
use Illuminate\Support\Facades\Auth; 

class TwoFactorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Auth/VerifyTwoFactor', [
            'status' => session('status'),
        ]);
    }

    //verify token 
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'two_factor_code' => ['required', 'integer'],
        ]);

        $user = Auth::user();
        $roleName = $user->role?->name;

        //validating code matching and timestamp threshold
        if ((int)$request->two_factor_code === (int)$user->two_factor_code && $user->two_factor_expires_at && now()->lessThanOrEqualTo($user->two_factor_expires_at)) {
            
            //wipe the two factor code and expiration timestamp
            $user->forceFill([
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
            ])->save();

            // Mark the user as verified
            session(['2fa_verified' => true]);

            return match ($roleName) {
                'admin' => redirect()->route('admin.dashboard'),
                'frontdesk' => redirect()->route('frontdesk.queues.index'),
                'cashier' => redirect()->route('cashier.index'),
                default => redirect()->route('dashboard'),
            };
        } else {
            return redirect()->back()->withErrors(['two_factor_code' => 'The provided two-factor code is invalid. Please check your email for the correct code.']);
        }
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = Auth::user();

        //generating new two factor code and expiration
        $user->two_factor_code = rand(100000, 999999);
        $user->two_factor_expires_at = now()->addMinutes(10);
        $user->save();

        //send notification
        $user->notify(new SendTwoFactorCode());

        return redirect()->back()->with('status', 'A new two-factor code has been sent to your email.');
    }
}
