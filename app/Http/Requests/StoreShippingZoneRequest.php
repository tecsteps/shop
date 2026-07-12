<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'countries_json' => ['required', 'array', 'min:1'], 'countries_json.*' => ['string', 'size:2'], 'regions_json' => ['sometimes', 'array']];
    }
}
