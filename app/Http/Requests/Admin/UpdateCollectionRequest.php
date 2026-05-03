<?php

namespace App\Http\Requests\Admin;

use App\Enums\CollectionStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'handle' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::unique((new ProductCollection)->getTable(), 'handle')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId()))
                    ->ignore((int) $this->route('collection')),
            ],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'type' => ['sometimes', Rule::in(['manual', 'automated'])],
            'status' => ['sometimes', Rule::in(array_map(fn (CollectionStatus $status): string => $status->value, CollectionStatus::cases()))],
            'product_ids' => ['sometimes', 'nullable', 'array'],
            'product_ids.*' => [
                'integer',
                Rule::exists((new Product)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'add_product_ids' => ['sometimes', 'nullable', 'array'],
            'add_product_ids.*' => [
                'integer',
                Rule::exists((new Product)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'remove_product_ids' => ['sometimes', 'nullable', 'array'],
            'remove_product_ids.*' => [
                'integer',
                Rule::exists((new Product)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
        ];
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }
}
