<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()?->role?->name === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
