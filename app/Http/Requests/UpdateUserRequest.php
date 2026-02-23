<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()?->role?->name === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'unique:users,email,' . $this->route('user')],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
