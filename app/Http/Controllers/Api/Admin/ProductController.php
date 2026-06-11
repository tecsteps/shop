<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ProductListResource;
use App\Http\Resources\Admin\ProductResource;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Support\HandleGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Relations loaded for the full product resource (spec 02 section 3.2).
     */
    protected const array DETAIL_RELATIONS = [
        'options.values',
        'variants.optionValues.option',
        'variants.inventoryItem',
        'media',
        'collections',
    ];

    public function __construct(
        protected ProductService $productService,
        protected HandleGenerator $handleGenerator,
    ) {}

    /**
     * GET /api/admin/v1/stores/{storeId}/products
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'query' => ['nullable', 'string', 'max:255'],
            'collection_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['title_asc', 'title_desc', 'created_at_asc', 'created_at_desc', 'updated_at_desc'])],
        ]);

        [$sortColumn, $sortDirection] = match ($validated['sort'] ?? 'updated_at_desc') {
            'title_asc' => ['title', 'asc'],
            'title_desc' => ['title', 'desc'],
            'created_at_asc' => ['created_at', 'asc'],
            'created_at_desc' => ['created_at', 'desc'],
            default => ['updated_at', 'desc'],
        };

        $products = Product::query()
            ->with('media')
            ->withCount('variants')
            ->addSelect([
                'total_inventory' => InventoryItem::query()
                    ->withoutGlobalScopes()
                    ->join('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->selectRaw('coalesce(sum(inventory_items.quantity_on_hand), 0)'),
            ])
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(filled($validated['query'] ?? null), function ($query) use ($validated): void {
                $term = '%'.$validated['query'].'%';
                $query->where(fn ($inner) => $inner
                    ->where('title', 'like', $term)
                    ->orWhere('vendor', 'like', $term)
                    ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', $term)));
            })
            ->when(isset($validated['collection_id']), fn ($query) => $query
                ->whereHas('collections', fn ($collections) => $collections->whereKey($validated['collection_id'])))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(perPage: (int) ($validated['per_page'] ?? 15));

        return response()->json([
            'data' => ProductListResource::collection($products->items())->resolve(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/admin/v1/stores/{storeId}/products
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateProductPayload($request, creating: true);

        $product = DB::transaction(function () use ($validated): Product {
            $store = app('current_store');

            $product = new Product([
                'title' => $validated['title'],
                'handle' => $validated['handle']
                    ?? $this->handleGenerator->generate($validated['title'], 'products', $store->getKey()),
                'description_html' => $validated['description_html'] ?? null,
                'vendor' => $validated['vendor'] ?? null,
                'product_type' => $validated['product_type'] ?? null,
                'status' => $validated['status'] ?? ProductStatus::Draft->value,
                'tags' => $validated['tags'] ?? [],
            ]);
            $product->store_id = $store->getKey();

            if ($product->status === ProductStatus::Active) {
                $product->published_at = now();
            }

            $product->save();

            $optionValueIds = $this->createOptions($product, $validated);

            foreach ($validated['variants'] as $position => $variantPayload) {
                $this->createVariantFromPayload($product, $variantPayload, $position + 1, $optionValueIds);
            }

            $this->ensureSingleDefaultVariant($product);

            if (! empty($validated['collections'])) {
                $product->collections()->sync($validated['collections']);
            }

            return $product;
        });

        return (new ProductResource($product->load(self::DETAIL_RELATIONS)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/admin/v1/stores/{storeId}/products/{productId}
     */
    public function show(int $storeId, int $productId): ProductResource
    {
        return new ProductResource(
            Product::query()->with(self::DETAIL_RELATIONS)->findOrFail($productId),
        );
    }

    /**
     * PUT /api/admin/v1/stores/{storeId}/products/{productId}
     */
    public function update(Request $request, int $storeId, int $productId): ProductResource
    {
        $product = Product::query()->findOrFail($productId);

        $validated = $this->validateProductPayload($request, creating: false, product: $product);

        DB::transaction(function () use ($product, $validated): void {
            $fields = array_intersect_key($validated, array_flip([
                'title', 'handle', 'description_html', 'vendor', 'product_type', 'tags',
            ]));

            if ($fields !== []) {
                $this->productService->update($product, $fields);
            }

            if (isset($validated['status'])) {
                $this->transitionStatus($product, ProductStatus::from($validated['status']));
            }

            foreach ($validated['variants'] ?? [] as $variantPayload) {
                $this->upsertVariant($product, $variantPayload);
            }

            if (array_key_exists('collections', $validated)) {
                $product->collections()->sync($validated['collections'] ?? []);
            }
        });

        return new ProductResource($product->refresh()->load(self::DETAIL_RELATIONS));
    }

    /**
     * DELETE /api/admin/v1/stores/{storeId}/products/{productId}
     *
     * Archives the product (soft delete, spec 02 section 3.2).
     */
    public function destroy(int $storeId, int $productId): JsonResponse
    {
        $product = Product::query()->findOrFail($productId);

        $this->transitionStatus($product, ProductStatus::Archived);

        return response()->json([
            'data' => [
                'id' => $product->getKey(),
                'status' => $product->status->value,
                'updated_at' => $product->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateProductPayload(Request $request, bool $creating, ?Product $product = null): array
    {
        $storeId = app('current_store')->getKey();

        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'handle' => [
                'nullable', 'string', 'max:255',
                Rule::unique('products', 'handle')
                    ->where('store_id', $storeId)
                    ->ignore($product?->getKey()),
            ],
            'description_html' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in($creating ? ['draft', 'active'] : ['draft', 'active', 'archived'])],
            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'options' => ['nullable', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.position' => ['nullable', 'integer', 'min:1', 'max:3'],
            'variants' => [$creating ? 'required' : 'sometimes', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.price_amount' => [$creating ? 'required' : 'sometimes', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['nullable', 'string', 'size:3'],
            'variants.*.weight_g' => ['nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['nullable', 'boolean'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.position' => ['nullable', 'integer', 'min:1'],
            'variants.*.status' => ['nullable', Rule::in(['active', 'archived'])],
            'variants.*.option_values' => ['nullable', 'array'],
            'variants.*.option_values.*.option_name' => ['required', 'string', 'max:255'],
            'variants.*.option_values.*.value' => ['required', 'string', 'max:255'],
            'variants.*.inventory' => ['nullable', 'array'],
            'variants.*.inventory.quantity_on_hand' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['nullable', Rule::in(['deny', 'continue'])],
            'collections' => ['nullable', 'array'],
            'collections.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $storeId)],
        ]);
    }

    /**
     * Create the product's options; values are collected from the variants'
     * option_values in order of first appearance. Returns option value ids
     * keyed by "Option name|Value".
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, int>
     */
    protected function createOptions(Product $product, array $validated): array
    {
        $optionValueIds = [];

        $options = collect($validated['options'] ?? [])
            ->sortBy(fn (array $option, int $index): int => (int) ($option['position'] ?? $index + 1))
            ->values();

        foreach ($options as $position => $option) {
            $productOption = $product->options()->create([
                'name' => $option['name'],
                'position' => $position,
            ]);

            $values = collect($validated['variants'])
                ->flatMap(fn (array $variant) => collect($variant['option_values'] ?? [])
                    ->filter(fn (array $value): bool => $value['option_name'] === $option['name'])
                    ->pluck('value'))
                ->unique()
                ->values();

            foreach ($values as $valuePosition => $value) {
                $optionValue = $productOption->values()->create([
                    'value' => $value,
                    'position' => $valuePosition,
                ]);

                $optionValueIds[$option['name'].'|'.$value] = $optionValue->getKey();
            }
        }

        return $optionValueIds;
    }

    /**
     * Create a variant with its inventory item and option value links.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $optionValueIds
     */
    protected function createVariantFromPayload(Product $product, array $payload, int $position, array $optionValueIds): ProductVariant
    {
        $variant = $this->productService->createVariant($product, [
            'sku' => $payload['sku'] ?? null,
            'barcode' => $payload['barcode'] ?? null,
            'price_amount' => $payload['price_amount'] ?? 0,
            'compare_at_amount' => $payload['compare_at_amount'] ?? null,
            'weight_g' => $payload['weight_g'] ?? null,
            'requires_shipping' => $payload['requires_shipping'] ?? true,
            'is_default' => $payload['is_default'] ?? false,
            'status' => $payload['status'] ?? 'active',
            'position' => $payload['position'] ?? $position,
        ]);

        $valueIds = collect($payload['option_values'] ?? [])
            ->map(fn (array $value): ?int => $optionValueIds[$value['option_name'].'|'.$value['value']] ?? null)
            ->filter()
            ->all();

        if ($valueIds !== []) {
            $variant->optionValues()->sync($valueIds);
        }

        $inventory = $payload['inventory'] ?? null;

        if ($inventory !== null) {
            $variant->inventoryItem?->update([
                'quantity_on_hand' => $inventory['quantity_on_hand'] ?? 0,
                'policy' => $inventory['policy'] ?? InventoryPolicy::Deny->value,
            ]);
        }

        return $variant;
    }

    /**
     * Update an existing variant by id or create a new one (spec 02
     * section 3.2: variants can be added or updated by ID).
     *
     * @param  array<string, mixed>  $payload
     */
    protected function upsertVariant(Product $product, array $payload): void
    {
        $variant = isset($payload['id'])
            ? $product->variants()->findOrFail($payload['id'])
            : null;

        if ($variant === null) {
            $position = (int) $product->variants()->max('position') + 1;
            $this->createVariantFromPayload($product, $payload, $position, []);

            return;
        }

        $variant->fill(array_intersect_key($payload, array_flip([
            'sku', 'barcode', 'price_amount', 'compare_at_amount', 'weight_g',
            'requires_shipping', 'is_default', 'position', 'status',
        ])))->save();

        $inventory = $payload['inventory'] ?? null;

        if ($inventory !== null) {
            $variant->inventoryItem?->update(array_intersect_key($inventory, array_flip([
                'quantity_on_hand', 'policy',
            ])));
        }
    }

    /**
     * Exactly one variant must be the default; fall back to the first one.
     */
    protected function ensureSingleDefaultVariant(Product $product): void
    {
        if (! $product->variants()->where('is_default', true)->exists()) {
            $product->variants()->orderBy('position')->limit(1)->update(['is_default' => true]);
        }
    }

    /**
     * Run the product status state machine, mapping guard failures to 422.
     */
    protected function transitionStatus(Product $product, ProductStatus $status): void
    {
        try {
            $this->productService->transitionStatus($product, $status);
        } catch (InvalidProductTransitionException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }
    }
}
