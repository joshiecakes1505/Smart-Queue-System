<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->check() && Auth::guard('admin')->user()?->role?->name === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $prefix = $this->input('prefix');

        $this->merge([
            'prefix' => $prefix ? strtoupper((string) $prefix) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', 'unique:service_categories,name'],
            'prefix' => ['required', 'string', 'size:1', 'regex:/^[A-Z]$/', 'unique:service_categories,prefix'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_queues_per_day' => ['nullable', 'integer', 'min:1'],
            'avg_service_seconds' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
