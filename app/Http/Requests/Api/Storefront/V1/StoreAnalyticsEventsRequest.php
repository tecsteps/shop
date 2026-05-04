<?php

namespace App\Http\Requests\Api\Storefront\V1;

use App\Enums\AnalyticsEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAnalyticsEventsRequest extends FormRequest
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
        $minOccurredAt = now()->subHour()->format('Y-m-d H:i:s');
        $maxOccurredAt = now()->addHour()->format('Y-m-d H:i:s');

        return [
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', Rule::in(array_column(AnalyticsEventType::cases(), 'value'))],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.occurred_at' => ['required', 'date', "after_or_equal:{$minOccurredAt}", "before_or_equal:{$maxOccurredAt}"],
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
                    $properties = is_array($event) ? ($event['properties'] ?? []) : [];

                    if (is_array($properties) && $this->depth($properties) > 3) {
                        $validator->errors()->add("events.{$index}.properties", __('Properties may not be nested deeper than 3 levels.'));
                    }
                }
            },
        ];
    }

    /**
     * @param  array<mixed>  $value
     */
    private function depth(array $value): int
    {
        if ($value === []) {
            return 1;
        }

        $childDepth = collect($value)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): int => $this->depth($item))
            ->max() ?? 0;

        return 1 + $childDepth;
    }
}
