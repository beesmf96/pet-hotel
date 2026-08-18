<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // OAuth-only accounts have a null password, so there is nothing the user
            // could confirm. Everyone else must prove they know the current one.
            'current_password' => $this->user()->password === null
                ? ['nullable']
                : ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The current password is incorrect.',
        ];
    }
}
