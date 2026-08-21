<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return app()->bound('current_store') && Gate::allows('viewAny', \App\Models\Page::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['title' => ['sometimes', 'string', 'max:255'], 'handle' => ['sometimes', 'string', 'max:255'], 'body_html' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'in:draft,published']];
    }
}
