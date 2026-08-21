<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderExportRequest extends FormRequest
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
            'format' => ['sometimes', 'in:csv'],
            'filters' => ['nullable', 'array'],
            'filters.status' => ['nullable', 'string'],
            'filters.financial_status' => ['nullable', 'string'],
            'filters.created_after' => ['nullable', 'date'],
            'filters.created_before' => ['nullable', 'date', 'after_or_equal:filters.created_after'],
        ];
    }
}
