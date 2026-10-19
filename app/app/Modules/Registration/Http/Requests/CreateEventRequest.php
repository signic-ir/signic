<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Requests;

use App\Modules\Registration\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Give the event a schedule and a status when they are not supplied.
     */
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');

        if (is_string($status)) {
            $this->merge(['status' => strtoupper($status)]);
        }

        if (! $this->filled('starts_at')) {
            $this->merge(['starts_at' => now()->toDateTimeString()]);
        }

        if (! $this->filled('ends_at')) {
            $this->merge(['ends_at' => now()->addDay()->toDateTimeString()]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rules' => ['nullable', 'array'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'registration_start_at' => ['nullable', 'date'],
            'registration_end_at' => ['nullable', 'date'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in($this->statuses())],
            'is_registration_open' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid.',
        ];
    }

    /**
     * The event statuses the platform accepts.
     *
     * @return array<int, string>
     */
    protected function statuses(): array
    {
        return array_map(fn (EventStatus $status) => $status->value, EventStatus::cases());
    }
}
