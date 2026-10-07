<?php

declare(strict_types=1);

namespace App.Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OTPRequestRequest extends FormRequest
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
            'phone' => ['required', 'string', 'regex:/^[\+]?[0-9]{10,15}$/'],
        ];
    }
}