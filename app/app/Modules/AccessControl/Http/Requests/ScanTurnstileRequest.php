<?php

declare(strict_types=1);

namespace App\Modules\AccessControl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanTurnstileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token_hash' => ['required', 'string', 'max:255'],
            'turnstile_id' => ['required', 'integer', 'exists:turnstiles,id'],
            'event_type' => ['nullable', 'in:checkin,checkout,force_checkin,force_checkout'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'turnstile_id.exists' => 'The specified turnstile does not exist.',
            'event_type.in' => 'The specified event type is invalid.',
        ];
    }
}