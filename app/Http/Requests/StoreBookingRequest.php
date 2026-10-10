<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pet_id' => ['required', 'integer', 'exists:pets,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * The booking form prints these under the calendar, so they name the
     * action rather than the field.
     */
    public function messages(): array
    {
        return [
            'check_in.required' => 'Please select a check-in date.',
            'check_out.required' => 'Please select a check-out date.',
        ];
    }
}
