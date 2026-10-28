<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'checkout_key' => ['required', 'uuid'],
            'tickets' => ['required', 'array', 'max:30'],
            'tickets.*' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }
}
