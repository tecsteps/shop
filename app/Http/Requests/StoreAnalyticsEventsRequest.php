<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAnalyticsEventsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'in:page_view,product_view,add_to_cart,remove_from_cart,checkout_started,checkout_completed,search'],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.occurred_at' => ['required', 'date'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('events', []) as $index => $event) {
                try {
                    $occurredAt = new \DateTimeImmutable((string) ($event['occurred_at'] ?? ''));
                    if (abs(now()->getTimestamp() - $occurredAt->getTimestamp()) > 3600) {
                        $validator->errors()->add("events.$index.occurred_at", 'The event timestamp must be within one hour of the current time.');
                    }
                } catch (\Throwable) {
                    // The date rule reports malformed timestamps.
                }

                if ($this->jsonDepth($event['properties'] ?? []) > 3) {
                    $validator->errors()->add("events.$index.properties", 'Event properties may not be deeper than three levels.');
                }
            }
        });
    }

    private function jsonDepth(mixed $value): int
    {
        if (! is_array($value) || $value === []) {
            return 1;
        }

        return 1 + max(array_map(fn (mixed $item): int => $this->jsonDepth($item), $value));
    }
}
