<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'unique:events,slug', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'registration_start_at' => ['nullable', 'date', 'before:starts_at'],
            'registration_end_at' => ['nullable', 'date', 'before:starts_at'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'rules' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ];
    }
}