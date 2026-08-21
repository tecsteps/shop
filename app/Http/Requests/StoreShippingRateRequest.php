<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreShippingRateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return app()->bound('current_store') && Gate::allows('update', app('current_store'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:flat,weight,price,carrier'], 'price_amount' => ['nullable', 'integer', 'min:0'], 'currency' => ['nullable', 'size:3', 'uppercase'], 'config_json' => ['required', 'array'], 'is_active' => ['nullable', 'boolean'], 'estimated_days_min' => ['nullable', 'integer', 'min:0'], 'estimated_days_max' => ['nullable', 'integer', 'min:0']];
    }
}
