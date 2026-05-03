<?php

namespace App\Http\Requests\Api\Storefront\V1;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartRequest extends FormRequest
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
        $currencyRules = ['nullable', 'string', 'size:3'];
        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($store instanceof Store) {
            $currencyRules[] = Rule::in([$store->default_currency]);
        }

        return [
            'currency' => $currencyRules,
        ];
    }
}
