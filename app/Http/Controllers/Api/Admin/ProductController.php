<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ProductListResource;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin product REST API. Reads are store-scoped via the resolved
 * `current_store`; the route-level `ability` middleware enforces
 * `read-products` / `write-products` token scopes. All amounts are integers in
 * minor units (cents).
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    /**
     * List products with filtering, sorting, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:draft,active,archived'],
            'query' => ['sometimes', 'string'],
            'collection_id' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'in:title_asc,title_desc,created_at_asc,created_at_desc,updated_at_desc'],
        ]);

        $query = Product::query()
            ->withCount('variants')
            ->with(['variants.inventoryItem', 'media']);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['query'])) {
            $term = '%'.$validated['query'].'%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('title', 'like', $term)
                    ->orWhere('vendor', 'like', $term)
                    ->orWhereHas('variants', fn (Builder $variants) => $variants->where('sku', 'like', $term));
            });
        }

        if (! empty($validated['collection_id'])) {
            $query->whereHas('collections', fn (Builder $c) => $c->where('collections.id', $validated['collection_id']));
        }

        $this->applySort($query, $validated['sort'] ?? 'updated_at_desc');

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return ProductListResource::collection($paginator)->response();
    }

    /**
     * Create a product (with nested options, variants, inventory, collections).
     */
    public function store(Request $request): JsonResponse
    {
        $store = app('current_store');

        $data = $this->validatePayload($request, creating: true);

        $product = $this->products->create($store, $data);

        if (! empty($data['collections'])) {
            $this->syncCollections($product, $data['collections']);
        }

        return (new ProductResource($this->loadProduct($product)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single product with all relations.
     */
    public function show(Request $request, $store, int $productId): JsonResponse
    {
        $product = $this->resolveProduct($productId);

        return (new ProductResource($this->loadProduct($product)))->response();
    }

    /**
     * Update a product (partial updates supported).
     */
    public function update(Request $request, $store, int $productId): JsonResponse
    {
        $product = $this->resolveProduct($productId);

        $data = $this->validatePayload($request, creating: false);

        $product = $this->products->update($product, $data);

        if (array_key_exists('collections', $data)) {
            $this->syncCollections($product, $data['collections'] ?? []);
        }

        return (new ProductResource($this->loadProduct($product)))->response();
    }

    /**
     * Archive a product (soft delete via status transition).
     */
    public function destroy(Request $request, $store, int $productId): JsonResponse
    {
        $product = $this->resolveProduct($productId);

        $this->products->transitionStatus($product, ProductStatus::Archived);

        $product->refresh();

        return response()->json([
            'data' => [
                'id' => $product->id,
                'status' => $product->status->value,
                'updated_at' => $product->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Validate and normalize the product create/update payload.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:draft,active'],
            'tags' => ['sometimes', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'options' => ['sometimes', 'array', 'max:3'],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.position' => ['required_with:options', 'integer', 'min:1', 'max:3'],
            'variants' => [$creating ? 'required' : 'sometimes', 'array', 'min:1', 'max:100'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:255'],
            'variants.*.barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.price_amount' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['sometimes', 'string', 'size:3'],
            'variants.*.weight_g' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['sometimes', 'boolean'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.position' => ['sometimes', 'integer'],
            'variants.*.status' => ['sometimes', 'string', 'in:active,archived'],
            'variants.*.option_values' => ['sometimes', 'array'],
            'variants.*.inventory' => ['sometimes', 'array'],
            'variants.*.inventory.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['sometimes', 'string', 'in:deny,continue'],
            'collections' => ['sometimes', 'array'],
            'collections.*' => ['integer'],
        ]);
    }

    /**
     * @param  list<int>  $collectionIds
     */
    private function syncCollections(Product $product, array $collectionIds): void
    {
        $product->collections()->sync($collectionIds);
    }

    private function resolveProduct(int $productId): Product
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            abort(404, 'The requested resource was not found.');
        }

        return $product;
    }

    private function loadProduct(Product $product): Product
    {
        return $product->load([
            'options.values',
            'variants.inventoryItem',
            'variants.optionValues',
            'media',
            'collections',
        ]);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'created_at_asc' => $query->orderBy('created_at'),
            'created_at_desc' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('updated_at'),
        };
    }
}
