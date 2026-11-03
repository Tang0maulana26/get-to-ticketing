<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $discount = $this->route('discount');
        $discountId = is_object($discount) ? $discount->id : $discount;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('discounts', 'code')->ignore($discountId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => [
                'required',
                'integer',
                'min:1',
                $this->input('type') === 'percent' ? 'max:100' : 'min:500',
            ],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->code)),
            'event_id' => $this->filled('event_id') ? (int) $this->event_id : null,
            'active' => $this->boolean('active'),
            'min_order_amount' => $this->min_order_amount ?? 0,
        ]);
    }
}

