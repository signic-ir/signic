<?php

declare(strict_types=1);

namespace App\Modules\Registration\Http\Requests;

use App\Modules\Registration\Enums\TicketType;
use App\Modules\Registration\Models\Attendee;
use App\Modules\Registration\Models\Event;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAttendeeRequest extends FormRequest
{
    /**
     * The attendee that is registered again, if any.
     */
    protected ?int $registeredAttendeeId = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Resolve the event the attendee is registered for.
     *
     * The event may be addressed by id or by name. When it is addressed by
     * name the id of the matching event is added to the request so that the
     * email can be checked for duplicates within that event.
     */
    protected function prepareForValidation(): void
    {
        $ticketType = $this->input('ticket_type');

        if (is_string($ticketType)) {
            $this->merge(['ticket_type' => strtoupper($ticketType)]);
        }

        $eventId = $this->input('event_id');

        if (! $eventId && $this->filled('event_name')) {
            $event = Event::where('name', $this->input('event_name'))->first();
            $eventId = $event?->id;

            if ($eventId) {
                $this->merge(['event_id' => $eventId]);
            }
        }

        if ($eventId) {
            $this->registeredAttendeeId = Attendee::where('event_id', $eventId)
                ->where('email', $this->input('email'))
                ->where('phone', $this->input('phone'))
                ->value('id');
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $eventId = $this->input('event_id');

        $emailRules = ['nullable', 'email', 'max:255'];

        if ($eventId) {
            $emailRules[] = Rule::unique('attendees', 'email')
                ->where(function ($query) use ($eventId) {
                    $query->where('event_id', $eventId);
                })
                ->ignore($this->registeredAttendeeId);
        }

        return [
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[\+]?[0-9]{10,15}$/'],
            'email' => $emailRules,
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'dietary_requirements' => ['nullable', 'string'],
            'accessibility_needs' => ['nullable', 'string'],
            'special_instructions' => ['nullable', 'string'],
            'ticket_type' => ['required', Rule::in($this->ticketTypes())],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Make sure the attendee is associated with an event and carries a name.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('event_id') && ! $this->filled('event_name')) {
                $validator->errors()->add('event_id', 'An event is required, pass an event_id or an event_name.');
            }

            if (! $this->filled('name') && ! $this->filled('first_name') && ! $this->filled('last_name')) {
                $validator->errors()->add('name', 'A name is required, pass a name or a first_name and last_name.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number is invalid.',
            'ticket_type.in' => 'The selected ticket type is invalid.',
            'email.unique' => 'The email has already been registered for this event (duplicate email).',
        ];
    }

    /**
     * The ticket types the platform accepts.
     *
     * @return array<int, string>
     */
    protected function ticketTypes(): array
    {
        return array_map(fn (TicketType $type) => $type->value, TicketType::cases());
    }
}
