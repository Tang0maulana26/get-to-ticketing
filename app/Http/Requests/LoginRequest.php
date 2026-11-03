<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the login form fields (supporting both email and username).
 */
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'login' => ['required_without:email', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
