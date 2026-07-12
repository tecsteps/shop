<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['amount' => ['required_without:lines', 'integer', 'min:1'], 'lines' => ['required_without:amount', 'array'], 'lines.*' => ['integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:1000'], 'restock' => ['sometimes', 'boolean']];
    }
}
