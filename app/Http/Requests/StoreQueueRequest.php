<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('frontdesk')->check()
            && Auth::guard('frontdesk')->user()?->role?->name === 'frontdesk';
    }

    public function rules(): array
    {
        return [
            'has_multiple_transactions' => ['required', 'boolean'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'service_category_ids' => ['nullable', 'array', 'min:1'],
            'service_category_ids.*' => ['integer', 'exists:service_categories,id'],
            'client_name' => ['nullable', 'string', 'max:191'],
            'client_type' => ['required', 'string', 'in:student,parent,visitor,senior_citizen,high_priority'],
            'phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $hasMultipleTransactions = filter_var(
            $this->input('has_multiple_transactions', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $serviceCategoryId = $this->input('service_category_id');
        $serviceCategoryIds = collect($this->input('service_category_ids', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        $this->merge([
            'has_multiple_transactions' => $hasMultipleTransactions,
            'service_category_id' => $serviceCategoryId !== null && $serviceCategoryId !== '' ? (int) $serviceCategoryId : null,
            'service_category_ids' => $hasMultipleTransactions ? $serviceCategoryIds : null,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('service_category_id', ['required'], function ($input) {
            return !((bool) ($input->has_multiple_transactions ?? false));
        });

        $validator->sometimes('service_category_ids', ['required', 'array', 'min:1'], function ($input) {
            return (bool) ($input->has_multiple_transactions ?? false);
        });
    }
}
