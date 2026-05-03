<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
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
            'type' => ['sometimes', Rule::in(array_map(fn (DiscountType $type): string => $type->value, DiscountType::cases()))],
            'code' => ['prohibited'],
            'value_type' => ['sometimes', Rule::in(array_map(fn (DiscountValueType $type): string => $type->value, DiscountValueType::cases()))],
            'value_amount' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'rules_json' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', Rule::in(array_map(fn (DiscountStatus $status): string => $status->value, DiscountStatus::cases()))],
        ];
    }
}
