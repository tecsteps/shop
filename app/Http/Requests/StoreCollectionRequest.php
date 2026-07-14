<?php

namespace App\Http\Requests;

use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Collection::class) ?? false;
    }

    public function rules(): array
    {
        $storeId = (int) ($this->route('storeId') ?? app('current_store')->id);

        return ['title' => ['required', 'string', 'max:255'], 'handle' => ['sometimes', 'string', 'max:255'], 'description_html' => ['nullable', 'string'], 'type' => ['sometimes', 'in:manual,automated'], 'status' => ['sometimes', 'in:draft,active,archived'], 'product_ids' => ['sometimes', 'array'], 'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $storeId)], 'add_product_ids' => ['sometimes', 'array'], 'add_product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $storeId)], 'remove_product_ids' => ['sometimes', 'array'], 'remove_product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $storeId)]];
    }
}
