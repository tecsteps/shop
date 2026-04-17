<?php

namespace App\Http\Requests\Storefront\Analytics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestAnalyticsEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', Rule::in([
                'page_view',
                'product_view',
                'add_to_cart',
                'remove_from_cart',
                'checkout_started',
                'checkout_completed',
                'search',
            ])],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['nullable', 'string', 'max:100'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.customer_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
