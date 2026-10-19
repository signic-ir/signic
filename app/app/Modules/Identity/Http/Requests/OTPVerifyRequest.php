<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OTPVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^[\+]?[0-9]{10,15}$/'],
            'otp' => ['required', 'string', 'length:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number is invalid.',
            'otp.length' => 'The OTP must be 6 digits.',
        ];
    }
}