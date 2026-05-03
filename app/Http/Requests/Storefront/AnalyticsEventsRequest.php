<?php

namespace App\Http\Requests\Storefront;

use App\Services\AnalyticsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnalyticsEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', Rule::in(AnalyticsService::EVENT_TYPES)],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.occurred_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('events', []) as $index => $event) {
                    try {
                        $occurredAt = Carbon::parse((string) data_get($event, 'occurred_at'));
                    } catch (\Throwable) {
                        continue;
                    }

                    if ($occurredAt->lt(now()->subHour()) || $occurredAt->gt(now()->addHour())) {
                        $validator->errors()->add("events.{$index}.occurred_at", 'The occurred at field must be within one hour of now.');
                    }

                    if ($this->depth((array) data_get($event, 'properties', [])) > 3) {
                        $validator->errors()->add("events.{$index}.properties", 'The properties field may not be nested more than three levels.');
                    }
                }
            },
        ];
    }

    private function depth(array $value): int
    {
        if ($value === []) {
            return 1;
        }

        $maxChildDepth = collect($value)
            ->filter(fn (mixed $child): bool => is_array($child))
            ->map(fn (array $child): int => $this->depth($child))
            ->max();

        return 1 + (int) ($maxChildDepth ?? 0);
    }
}
