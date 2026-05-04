<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\ProductResource;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'query' => ['nullable', 'string', 'max:255'],
            'collection_id' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['title_asc', 'title_desc', 'created_at_asc', 'created_at_desc', 'updated_at_desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Product::withoutGlobalScopes()
            ->with([
                'media',
                'variants.inventoryItem',
            ])
            ->withCount('variants')
            ->where('store_id', $store->getKey())
            ->when(data_get($validated, 'status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(data_get($validated, 'collection_id'), function (Builder $query, int $collectionId): void {
                $query->whereHas('collections', fn (Builder $query) => $query->withoutGlobalScopes()->whereKey($collectionId));
            })
            ->when(data_get($validated, 'query'), function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('title', 'like', $like)
                        ->orWhere('vendor', 'like', $like)
                        ->orWhereHas('variants', fn (Builder $query) => $query->withoutGlobalScopes()->where('sku', 'like', $like));
                });
            });

        $this->applySort($query, (string) data_get($validated, 'sort', 'updated_at_desc'));

        return ProductResource::collection($query->paginate((int) data_get($validated, 'per_page', 25)));
    }

    public function show(Request $request, Store $store, Product $product): ProductResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessProductBelongsToStore($product, $store);

        return ProductResource::make($this->loadProduct($product));
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessProductBelongsToStore(Product $product, Store $store): void
    {
        abort_unless((int) $product->store_id === $store->getKey(), 404);
    }

    private function loadProduct(Product $product): Product
    {
        return $product->load([
            'collections',
            'media',
            'options.values',
            'variants.inventoryItem',
            'variants.optionValues.option',
        ])->loadCount('variants');
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'title_asc' => $query->orderBy('title')->orderBy('id'),
            'title_desc' => $query->orderByDesc('title')->orderByDesc('id'),
            'created_at_asc' => $query->orderBy('created_at')->orderBy('id'),
            'created_at_desc' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderByDesc('updated_at')->orderByDesc('id'),
        };
    }
}
