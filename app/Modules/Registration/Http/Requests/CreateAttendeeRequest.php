<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAttendeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[\+]?[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'ticket_type' => ['required', 'in:general,vip,speaker,press,organizer,volunteer'],
            'dietary_requirements' => ['nullable', 'string'],
            'accessibility_needs' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number is invalid.',
            'ticket_type.in' => 'The selected ticket type is invalid.',
        ];
    }
}