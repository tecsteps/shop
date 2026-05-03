<?php

namespace App\Http\Requests\Admin;

use App\Enums\CollectionStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::unique((new ProductCollection)->getTable(), 'handle')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'description_html' => ['nullable', 'string', 'max:65535'],
            'type' => ['required', Rule::in(['manual', 'automated'])],
            'status' => ['nullable', Rule::in(array_map(fn (CollectionStatus $status): string => $status->value, CollectionStatus::cases()))],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => [
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
