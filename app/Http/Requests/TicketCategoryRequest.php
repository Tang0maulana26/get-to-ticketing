<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('ticket_categories')->where('event_id', $this->route('event')->id)->ignore($this->route('ticketCategory')),
            ],
            'description' => ['nullable', 'string', 'max:300'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'capacity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'max_per_order' => ['required', 'integer', 'between:1,20'],
            'active' => ['required', 'boolean'],
        ];
    }
}
