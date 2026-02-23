<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()?->role?->name === 'frontdesk';
    }

    public function rules(): array
    {
        return [
            'service_category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'client_name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
