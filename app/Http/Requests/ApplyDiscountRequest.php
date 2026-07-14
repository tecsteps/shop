<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app()->bound('current_store');
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:100']];
    }
}
