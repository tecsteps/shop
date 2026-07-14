<?php

namespace App\Http\Requests;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Page::class) ?? false;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'handle' => ['sometimes', 'string', 'max:255'], 'body_html' => ['nullable', 'string'], 'status' => ['sometimes', 'in:draft,published,archived'], 'published_at' => ['nullable', 'date']];
    }
}
