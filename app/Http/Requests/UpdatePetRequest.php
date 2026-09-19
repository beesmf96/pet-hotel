<?php

namespace App\Http\Requests;

use App\Enums\PetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'species' => ['required', Rule::enum(PetType::class)],
            'breed' => ['nullable', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
