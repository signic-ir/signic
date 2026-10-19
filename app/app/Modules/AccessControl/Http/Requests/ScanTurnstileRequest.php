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
            'qr_token' => ['required_without:token_hash', 'string', 'max:255'],
            'token_hash' => ['required_without:qr_token', 'string', 'max:255'],
            'turnstile_id' => ['required_without_all:turnstile,turnstile_name'],
            'turnstile' => ['nullable', 'string', 'max:255'],
            'turnstile_name' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'in:CHECK_IN,CHECK_OUT,FORCE_CHECK_IN,FORCE_CHECK_OUT'],
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
