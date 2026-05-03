<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(array_map(fn (ProductStatus $status): string => $status->value, ProductStatus::cases()))],
            'query' => ['nullable', 'string', 'max:255'],
            'collection_id' => [
                'nullable',
                'integer',
                Rule::exists((new ProductCollection)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['title_asc', 'title_desc', 'created_at_asc', 'created_at_desc', 'updated_at_desc'])],
        ];
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }
}
