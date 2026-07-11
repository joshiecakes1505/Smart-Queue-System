<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    }
    public function update(Request $request)
        {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $user = $request->user();

            $user->update([
                'name' => $validated['name'],
            ]);

            return response()->json([
                'message' => 'Profile updated successfully.',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check(
            $validated['current_password'],
            $user->password
        )) {

            return response()->json([
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }
}