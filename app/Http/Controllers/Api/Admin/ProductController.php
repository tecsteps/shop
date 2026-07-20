<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ProductListResource;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin products API (spec 02 §3.2).
 */
class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    /**
     * GET /api/admin/v1/stores/{storeId}/products — list with filtering,
     * sorting, and pagination.
     */
    public function index(Request $request, int $storeId): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'query' => ['sometimes', 'string', 'max:255'],
            'collection_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in(['title_asc', 'title_desc', 'created_at_asc', 'created_at_desc', 'updated_at_desc'])],
        ]);

        $query = Product::query()->with(['variants.inventoryItem', 'media']);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (($validated['query'] ?? '') !== '') {
            $term = '%'.$validated['query'].'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('vendor', 'like', $term)
                    ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', $term));
            });
        }

        if (isset($validated['collection_id'])) {
            $query->whereHas('collections', fn ($collections) => $collections->where('collections.id', (int) $validated['collection_id']));
        }

        match ($validated['sort'] ?? 'updated_at_desc') {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'created_at_asc' => $query->orderBy('created_at'),
            'created_at_desc' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('updated_at'),
        };

        return ProductListResource::collection(
            $query->paginate((int) ($validated['per_page'] ?? 25)),
        );
    }

    /**
     * POST /api/admin/v1/stores/{storeId}/products — nested create via
     * the ProductService (options, variants, inventory, collections).
     */
    public function store(Request $request, int $storeId): JsonResponse
    {
        $validated = $request->validate($this->productRules());

        /** @var Store $store */
        $store = app('current_store');

        $product = $this->products->create($store, $this->mapToServicePayload($validated, $store));

        if (($validated['collections'] ?? []) !== []) {
            $this->syncCollections($product, $validated['collections']);
        }

        return (new ProductResource($product->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/admin/v1/stores/{storeId}/products/{productId} — full
     * product with variants, options, media, and collections.
     */
    public function show(Request $request, int $storeId, int $productId): ProductResource
    {
        return new ProductResource(Product::query()->findOrFail($productId));
    }

    /**
     * PUT /api/admin/v1/stores/{storeId}/products/{productId} — partial
     * update. Status changes go through the state machine.
     */
    public function update(Request $request, int $storeId, int $productId): JsonResponse|ProductResource
    {
        $validated = $request->validate($this->productRules(partial: true));

        $product = Product::query()->findOrFail($productId);

        /** @var Store $store */
        $store = app('current_store');

        $servicePayload = $this->mapToServicePayload($validated, $store);

        if ($servicePayload !== []) {
            $product = $this->products->update($product, $servicePayload);
        }

        if (isset($validated['status']) && $product->status !== ProductStatus::from($validated['status'])) {
            try {
                $this->products->transitionStatus($product, ProductStatus::from($validated['status']));
            } catch (InvalidProductTransitionException $exception) {
                abort(409, $exception->getMessage());
            }
        }

        if (($validated['collections'] ?? null) !== null) {
            $this->syncCollections($product, $validated['collections']);
        }

        return new ProductResource($product->refresh());
    }

    /**
     * DELETE /api/admin/v1/stores/{storeId}/products/{productId} —
     * archive (soft delete). Products with orders cannot be hard-deleted.
     */
    public function destroy(Request $request, int $storeId, int $productId): JsonResponse
    {
        $product = Product::query()->findOrFail($productId);

        try {
            if ($product->status !== ProductStatus::Archived) {
                $this->products->transitionStatus($product, ProductStatus::Archived);
            }
        } catch (InvalidProductTransitionException $exception) {
            abort(409, $exception->getMessage());
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'status' => $product->status->value,
                'updated_at' => $product->updated_at?->toIso8601ZuluString(),
            ],
        ]);
    }

    /**
     * POST /api/admin/v1/stores/{storeId}/products/{productId}/media/presign-upload
     * — stub presigned upload: creates the media row in processing state
     * and returns the storage target (spec 02 §3.2).
     */
    public function presignUpload(Request $request, int $storeId, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['required', Rule::in(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'video/mp4'])],
            'byte_size' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::query()->findOrFail($productId);

        $isVideo = $validated['content_type'] === 'video/mp4';
        $maxBytes = $isVideo ? 500 * 1024 * 1024 : 50 * 1024 * 1024;

        if ((int) $validated['byte_size'] > $maxBytes) {
            throw ValidationException::withMessages([
                'byte_size' => ['The file exceeds the maximum allowed size.'],
            ]);
        }

        $extension = pathinfo($validated['filename'], PATHINFO_EXTENSION) ?: ($isVideo ? 'mp4' : 'jpg');
        $storageKey = sprintf('stores/%d/products/%d/media/%s.%s', $storeId, $product->id, (string) Str::uuid(), $extension);

        $media = $product->media()->create([
            'type' => $isVideo ? MediaType::Video : MediaType::Image,
            'storage_key' => $storageKey,
            'mime_type' => $validated['content_type'],
            'byte_size' => $validated['byte_size'],
            'position' => ((int) $product->media()->max('position')) + 1,
            'status' => MediaStatus::Processing,
        ]);

        // Stub URL: there is no real object storage behind this build.
        $uploadUrl = url('/storage/'.$storageKey).'?expires='.now()->addMinutes(10)->getTimestamp().'&signature='.Str::random(40);

        return response()->json([
            'upload_url' => $uploadUrl,
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => $validated['content_type'],
            ],
            'storage_key' => $storageKey,
            'media_id' => $media->id,
            'expires_at' => now()->addMinutes(10)->toIso8601ZuluString(),
        ], 201);
    }

    /**
     * Validation rules for create (all required per spec) and partial
     * update (everything optional).
     *
     * @return array<string, mixed>
     */
    private function productRules(bool $partial = false): array
    {
        $required = fn (array $rules): array => $partial ? ['sometimes', ...$rules] : $rules;

        return [
            'title' => $required(['required', 'string', 'max:255']),
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in([ProductStatus::Draft->value, ProductStatus::Active->value])],
            'tags' => ['sometimes', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'options' => ['sometimes', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.position' => ['required', 'integer', 'min:1', 'max:3'],
            'variants' => $partial ? ['sometimes', 'array', 'max:100'] : ['required', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['sometimes', 'integer'],
            'variants.*.sku' => $partial ? ['sometimes', 'nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'variants.*.barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.price_amount' => $partial ? ['sometimes', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'variants.*.weight_g' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['sometimes', 'boolean'],
            'variants.*.position' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.option_values' => ['sometimes', 'array'],
            'variants.*.option_values.*.option_name' => ['required', 'string', 'max:255'],
            'variants.*.option_values.*.value' => ['required', 'string', 'max:255'],
            'variants.*.inventory' => ['sometimes', 'array'],
            'variants.*.inventory.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['sometimes', Rule::in(['deny', 'continue'])],
            'collections' => ['sometimes', 'array'],
            'collections.*' => ['integer'],
        ];
    }

    /**
     * Map the API request shape onto the ProductService's internal
     * payload shape: option values are derived from the variants'
     * option_values, and variants match by plain value lists.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapToServicePayload(array $validated, Store $store): array
    {
        $payload = Arr::only($validated, [
            'title', 'handle', 'description_html', 'vendor', 'product_type', 'tags',
        ]);

        if (array_key_exists('options', $validated)) {
            $payload['options'] = collect($validated['options'])->map(fn (array $option): array => [
                'name' => $option['name'],
                'values' => collect($validated['variants'] ?? [])
                    ->flatMap(fn (array $variant): array => $variant['option_values'] ?? [])
                    ->where('option_name', $option['name'])
                    ->pluck('value')
                    ->unique()
                    ->values()
                    ->all(),
            ])->all();
        }

        if (array_key_exists('variants', $validated)) {
            $variants = collect($validated['variants'])->map(fn (array $variant): array => array_merge(
                Arr::only($variant, ['id', 'sku', 'barcode', 'price_amount', 'compare_at_amount', 'weight_g', 'requires_shipping', 'position']),
                ['currency' => $variant['currency'] ?? $store->default_currency],
                ['option_values' => collect($variant['option_values'] ?? [])->pluck('value')->all()],
                isset($variant['inventory']) ? ['inventory' => $variant['inventory']] : [],
            ))->all();

            // Products without options take their single default variant.
            $payload['variants'] = array_key_exists('options', $validated) && $validated['options'] !== []
                ? $variants
                : [Arr::except($variants[0] ?? [], ['option_values'])];
        }

        return $payload;
    }

    /**
     * Attach the given collection ids (restricted to the current store)
     * with sequential pivot positions.
     *
     * @param  list<int>  $collectionIds
     */
    private function syncCollections(Product $product, array $collectionIds): void
    {
        $ownedIds = \App\Models\Collection::query()
            ->whereIn('id', $collectionIds)
            ->pluck('id');

        $product->collections()->sync(
            $ownedIds->mapWithKeys(fn (int $id, int $index): array => [$id => ['position' => $index]])->all(),
        );

        $product->unsetRelation('collections');
    }
}
