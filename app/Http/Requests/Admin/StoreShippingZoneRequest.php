<?php

namespace App\Http\Requests\Admin;

use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreShippingZoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'countries_json' => ['required', 'array', 'min:1'],
            'countries_json.*' => ['string', 'size:2'],
            'regions_json' => ['nullable', 'array'],
            'regions_json.*' => ['string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'countries_json' => collect($this->input('countries_json', []))
                ->map(fn (mixed $country): string => strtoupper((string) $country))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateCountryOverlap($validator);
            },
        ];
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }

    private function validateCountryOverlap(Validator $validator): void
    {
        $countries = collect($this->input('countries_json', []));
        $overlapping = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->get()
            ->flatMap(fn (ShippingZone $zone): array => $zone->countries_json ?? [])
            ->intersect($countries)
            ->values();

        if ($overlapping->isNotEmpty()) {
            $validator->errors()->add('countries_json', 'Countries already assigned to another shipping zone: '.$overlapping->implode(', '));
        }
    }
}
