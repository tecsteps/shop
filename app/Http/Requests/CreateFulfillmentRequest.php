<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['lines' => ['required', 'array', 'min:1'], 'lines.*' => ['required', 'integer', 'min:1'], 'tracking_company' => ['nullable', 'string', 'max:100'], 'tracking_number' => ['nullable', 'string', 'max:255'], 'tracking_url' => ['nullable', 'url']];
    }
}
