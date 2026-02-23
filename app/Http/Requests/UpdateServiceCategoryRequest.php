<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()?->role?->name === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', 'unique:service_categories,name,' . $this->route('service_category')],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_queues_per_day' => ['nullable', 'integer', 'min:1'],
            'avg_service_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
