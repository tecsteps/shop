<?php

namespace App\Http\Requests\Auth;

use App\Tenancy\CurrentStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CurrentStore::class)->has();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('store_id', app(CurrentStore::class)->id()),
            ],
            'password' => 'required|string|min:8|confirmed',
            'marketing_opt_in' => 'sometimes|boolean',
        ];
    }
}
