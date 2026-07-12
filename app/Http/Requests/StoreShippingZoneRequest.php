<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::forUser($this->user())->allows('manage-shipping');
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'countries_json' => ['required', 'array', 'min:1'], 'countries_json.*' => ['string', 'size:2'], 'regions_json' => ['sometimes', 'array']];
    }
}
