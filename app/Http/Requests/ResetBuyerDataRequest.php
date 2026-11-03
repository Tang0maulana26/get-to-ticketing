<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetBuyerDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'confirmation_phrase' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (trim((string) $value) !== 'RESET-DATA-PEMBELI') {
                        $fail('Frasa konfirmasi tidak sesuai. Harap ketik "RESET-DATA-PEMBELI" dengan tepat.');
                    }
                },
            ],
        ];
    }
}

