<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()?->role?->name === 'admin';
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
