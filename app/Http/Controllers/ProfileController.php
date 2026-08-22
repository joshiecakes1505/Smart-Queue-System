<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Enable two-factor authentication for the current user.
     */
    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $request->user()->enableTwoFactor();

        return back();
    }

    /**
     * Disable two-factor authentication for the current user.
     */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->user()->disableTwoFactor();

        return back();
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $role = $user->role?->name;

        if ($role === 'admin') {
            return back()->withErrors([
                'password' => 'Admin accounts cannot be disabled from the profile page.',
            ]);
        }

        if (in_array($role, ['admin', 'frontdesk', 'cashier', 'web'], true)) {
            Auth::guard($role)->logout();
        } else {
            Auth::logout();
        }

        $user->disable();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('status', 'Your account has been disabled.');
    }
}
