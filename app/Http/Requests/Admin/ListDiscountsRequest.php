<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListDiscountsRequest extends FormRequest
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
            'type' => ['nullable', Rule::in(array_map(fn (DiscountType $type): string => $type->value, DiscountType::cases()))],
            'status' => ['nullable', Rule::in(array_map(fn (DiscountStatus $status): string => $status->value, DiscountStatus::cases()))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
