<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Actions\SanitizeHtml;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\CreateProductMediaUploadRequest;
use App\Http\Requests\Api\Admin\V1\StoreProductRequest;
use App\Http\Requests\Api\Admin\V1\UpdateProductRequest;
use App\Http\Resources\Admin\V1\ProductResource;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);
        abort_unless($request->user()?->can('viewAny', Product::class), 403);

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
        abort_unless($request->user()?->can('view', $product), 403);

        return ProductResource::make($this->loadProduct($product));
    }

    public function store(StoreProductRequest $request, Store $store, ProductService $products): JsonResponse
    {
        $this->authorizeStore($request, $store);
        abort_unless($request->user()?->can('create', Product::class), 403);

        $validated = $request->validated();

        try {
            $product = DB::transaction(function () use ($products, $store, $validated): Product {
                $product = $products->create($store, $this->attributesForCreate($validated, $store));

                if (array_key_exists('collections', $validated)) {
                    $product->collections()->sync($this->integerList($validated['collections']));
                }

                return $product->refresh();
            });
        } catch (InvalidProductTransitionException|InvalidArgumentException|RuntimeException $exception) {
            $this->throwProductValidationException($exception);
        }

        return ProductResource::make($this->loadProduct($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Store $store, Product $product, ProductService $products): ProductResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessProductBelongsToStore($product, $store);
        abort_unless($request->user()?->can('update', $product), 403);

        $validated = $request->validated();

        if (($validated['status'] ?? null) === ProductStatus::Archived->value) {
            abort_unless($request->user()?->can('archive', $product), 403);
        }

        try {
            $product = DB::transaction(function () use ($products, $store, $product, $validated): Product {
                $product = Product::withoutGlobalScopes()
                    ->whereKey($product->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $attributes = $this->attributesForUpdate($validated);
                $requestedStatus = null;

                if (array_key_exists('status', $attributes)) {
                    $requestedStatus = ProductStatus::from((string) $attributes['status']);
                    unset($attributes['status']);
                }

                if ($attributes !== []) {
                    $product = $products->update($product, $attributes);
                }

                if (array_key_exists('options', $validated)) {
                    $this->replaceProductOptions($product, $this->optionPayload($validated));
                }

                if (array_key_exists('variants', $validated)) {
                    $this->syncProductVariants($product, $store, $validated['variants']);
                }

                if (array_key_exists('collections', $validated)) {
                    $product->collections()->sync($this->integerList($validated['collections']));
                }

                if ($requestedStatus instanceof ProductStatus && $requestedStatus !== $product->refresh()->status) {
                    $products->transitionStatus($product, $requestedStatus);
                }

                return $product->refresh();
            });
        } catch (InvalidProductTransitionException|InvalidArgumentException|RuntimeException $exception) {
            $this->throwProductValidationException($exception);
        }

        return ProductResource::make($this->loadProduct($product));
    }

    public function destroy(Request $request, Store $store, Product $product, ProductService $products): JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessProductBelongsToStore($product, $store);
        abort_unless($request->user()?->can('archive', $product), 403);

        $products->transitionStatus($product, ProductStatus::Archived);

        return response()->json([
            'data' => [
                'id' => $product->getKey(),
                'status' => ProductStatus::Archived->value,
                'updated_at' => $product->refresh()->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function presignUpload(CreateProductMediaUploadRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessProductBelongsToStore($product, $store);
        abort_unless($request->user()?->can('update', $product), 403);

        $validated = $request->validated();
        $expiresAt = now()->addMinutes(10);
        $storageKey = sprintf(
            'media/originals/%d/%s.%s',
            $product->getKey(),
            Str::uuid(),
            $this->extensionForContentType((string) $validated['content_type']),
        );
        $media = ProductMedia::withoutGlobalScopes()->create([
            'product_id' => $product->getKey(),
            'type' => $this->mediaTypeForContentType((string) $validated['content_type']),
            'storage_key' => $storageKey,
            'alt_text' => $product->title,
            'mime_type' => $validated['content_type'],
            'byte_size' => (int) $validated['byte_size'],
            'position' => $this->nextMediaPosition($product),
            'status' => MediaStatus::Processing,
        ]);
        $upload = $this->temporaryUploadPayload(Storage::disk('public'), $storageKey, $expiresAt);

        return response()->json([
            'upload_url' => $upload['url'],
            'method' => 'PUT',
            'headers' => [
                ...$upload['headers'],
                'Content-Type' => $validated['content_type'],
            ],
            'storage_key' => $storageKey,
            'media_id' => $media->getKey(),
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('sanctum_personal_access_token')) {
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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForCreate(array $validated, Store $store): array
    {
        $attributes = [
            'title' => $validated['title'],
            'description_html' => $this->sanitizeHtml($validated['description_html'] ?? null),
            'vendor' => $validated['vendor'] ?? null,
            'product_type' => $validated['product_type'] ?? null,
            'status' => $validated['status'] ?? ProductStatus::Draft->value,
            'tags' => $this->tagList($validated['tags'] ?? []),
            'options' => $this->optionPayload($validated),
            'variants' => $this->variantPayloads($validated['variants'], $store, true),
        ];

        if (filled($validated['handle'] ?? null)) {
            $attributes['handle'] = Str::slug((string) $validated['handle']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForUpdate(array $validated): array
    {
        $attributes = Arr::only($validated, [
            'title',
            'description_html',
            'vendor',
            'product_type',
            'status',
        ]);

        if (array_key_exists('tags', $validated)) {
            $attributes['tags'] = $this->tagList($validated['tags']);
        }

        if (array_key_exists('description_html', $attributes)) {
            $attributes['description_html'] = $this->sanitizeHtml($attributes['description_html']);
        }

        if (filled($validated['handle'] ?? null)) {
            $attributes['handle'] = Str::slug((string) $validated['handle']);
        }

        return $attributes;
    }

    private function sanitizeHtml(?string $html): ?string
    {
        $sanitized = app(SanitizeHtml::class)($html);

        return $sanitized === '' ? null : $sanitized;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{name: string, position: int, values: array<int, array{value: string, position: int}>}>
     */
    private function optionPayload(array $validated): array
    {
        $declaredOptions = collect($validated['options'] ?? []);

        if ($declaredOptions->isEmpty()) {
            $declaredOptions = collect($validated['variants'] ?? [])
                ->flatMap(fn (array $variant): array => $variant['option_values'] ?? [])
                ->pluck('option_name')
                ->filter()
                ->unique()
                ->values()
                ->map(fn (string $name, int $position): array => [
                    'name' => $name,
                    'position' => $position + 1,
                ]);
        }

        return $declaredOptions
            ->map(function (array $option, int $position) use ($validated): array {
                $name = trim((string) $option['name']);
                $values = collect($option['values'] ?? [])
                    ->merge($this->variantOptionValues($validated['variants'] ?? [], $name))
                    ->map(fn (mixed $value): string => trim((string) $value))
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'name' => $name,
                    'position' => (int) ($option['position'] ?? ($position + 1)),
                    'values' => $values
                        ->map(fn (string $value, int $valuePosition): array => [
                            'value' => $value,
                            'position' => $valuePosition + 1,
                        ])
                        ->all(),
                ];
            })
            ->filter(fn (array $option): bool => $option['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return list<string>
     */
    private function variantOptionValues(array $variants, string $optionName): array
    {
        return collect($variants)
            ->flatMap(fn (array $variant): array => $variant['option_values'] ?? [])
            ->filter(fn (array $value): bool => ($value['option_name'] ?? null) === $optionName)
            ->pluck('value')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return array<int, array<string, mixed>>
     */
    private function variantPayloads(array $variants, Store $store, bool $creating = false): array
    {
        return collect($variants)
            ->values()
            ->map(fn (array $variant, int $position): array => [
                'sku' => trim((string) $variant['sku']),
                'barcode' => $variant['barcode'] ?? null,
                'price_amount' => (int) $variant['price_amount'],
                'compare_at_amount' => array_key_exists('compare_at_amount', $variant) ? $variant['compare_at_amount'] : null,
                'currency' => strtoupper((string) ($variant['currency'] ?? $store->default_currency)),
                'weight_g' => array_key_exists('weight_g', $variant) ? $variant['weight_g'] : null,
                'requires_shipping' => (bool) data_get($variant, 'requires_shipping', true),
                'is_default' => (bool) $variant['is_default'],
                'position' => (int) data_get($variant, 'position', $position),
                'status' => data_get($variant, 'status', VariantStatus::Active->value),
                'quantity_on_hand' => data_get($variant, 'inventory.quantity_on_hand', $creating ? 0 : null),
                'inventory_policy' => data_get($variant, 'inventory.policy', $creating ? 'deny' : null),
                'options' => $this->variantOptions($variant),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $variant
     * @return array<string, string>
     */
    private function variantOptions(array $variant): array
    {
        return collect($variant['option_values'] ?? [])
            ->mapWithKeys(fn (array $optionValue): array => [
                (string) $optionValue['option_name'] => (string) $optionValue['value'],
            ])
            ->all();
    }

    /**
     * @param  array<int, array{name: string, position: int, values: array<int, array{value: string, position: int}>}>  $optionPayload
     */
    private function replaceProductOptions(Product $product, array $optionPayload): void
    {
        $syncedOptionIds = [];

        foreach ($optionPayload as $optionData) {
            $option = ProductOption::withoutGlobalScopes()
                ->where('product_id', $product->getKey())
                ->where('position', $optionData['position'])
                ->first()
                ?? $product->options()->create([
                    'name' => $optionData['name'],
                    'position' => $optionData['position'],
                ]);

            $option->forceFill([
                'name' => $optionData['name'],
                'position' => $optionData['position'],
            ])->save();

            $syncedOptionIds[] = $option->getKey();
            $syncedValueIds = [];

            foreach ($optionData['values'] as $valueData) {
                $value = ProductOptionValue::withoutGlobalScopes()
                    ->where('product_option_id', $option->getKey())
                    ->where('position', $valueData['position'])
                    ->first()
                    ?? $option->values()->create([
                        'value' => $valueData['value'],
                        'position' => $valueData['position'],
                    ]);

                $value->forceFill([
                    'value' => $valueData['value'],
                    'position' => $valueData['position'],
                ])->save();

                $syncedValueIds[] = $value->getKey();
            }

            ProductOptionValue::withoutGlobalScopes()
                ->where('product_option_id', $option->getKey())
                ->when($syncedValueIds !== [], fn (Builder $query) => $query->whereNotIn('id', $syncedValueIds))
                ->delete();
        }

        ProductOption::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->when($syncedOptionIds !== [], fn (Builder $query) => $query->whereNotIn('id', $syncedOptionIds))
            ->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncProductVariants(Product $product, Store $store, array $variants): void
    {
        $variantPayloads = $this->variantPayloads($variants, $store);
        $syncedVariantIds = [];

        foreach ($variantPayloads as $position => $variantData) {
            $variantId = $variants[$position]['id'] ?? null;
            $attributes = Arr::only($variantData, [
                'sku',
                'barcode',
                'price_amount',
                'compare_at_amount',
                'currency',
                'weight_g',
                'requires_shipping',
                'is_default',
                'position',
                'status',
            ]);
            $variant = $variantId
                ? ProductVariant::withoutGlobalScopes()->where('product_id', $product->getKey())->findOrFail($variantId)
                : ProductVariant::withoutGlobalScopes()->create([
                    'product_id' => $product->getKey(),
                    ...$attributes,
                ]);

            if ($variantId) {
                $variant->forceFill($attributes)->save();
            }

            $this->syncVariantInventory($variant, $store, $variantData);
            $variant->optionValues()->sync($this->optionValueIdsForVariant($product, $variantData['options'] ?? []));
            $syncedVariantIds[] = $variant->getKey();
        }

        ProductVariant::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->when($syncedVariantIds !== [], fn (Builder $query) => $query->whereNotIn('id', $syncedVariantIds))
            ->get()
            ->each(function (ProductVariant $variant): void {
                if ($this->variantHasOrderLines($variant)) {
                    $variant->forceFill(['status' => VariantStatus::Archived])->save();

                    return;
                }

                $variant->delete();
            });
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    private function syncVariantInventory(ProductVariant $variant, Store $store, array $variantData): void
    {
        $inventory = InventoryItem::withoutGlobalScopes()
            ->where('variant_id', $variant->getKey())
            ->first();
        $attributes = [];

        if ($variantData['quantity_on_hand'] !== null) {
            $attributes['quantity_on_hand'] = (int) $variantData['quantity_on_hand'];
        }

        if ($variantData['inventory_policy'] !== null) {
            $attributes['policy'] = $variantData['inventory_policy'];
        }

        if ($inventory instanceof InventoryItem) {
            if ($attributes !== []) {
                $inventory->forceFill($attributes)->save();
            }

            return;
        }

        InventoryItem::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            'variant_id' => $variant->getKey(),
            'quantity_on_hand' => (int) ($variantData['quantity_on_hand'] ?? 0),
            'quantity_reserved' => 0,
            'policy' => $variantData['inventory_policy'] ?? 'deny',
        ]);
    }

    /**
     * @param  array<string, string>  $options
     * @return list<int>
     */
    private function optionValueIdsForVariant(Product $product, array $options): array
    {
        if ($options === []) {
            return [];
        }

        return collect($options)
            ->map(function (string $value, string $optionName) use ($product): int {
                $valueId = ProductOptionValue::withoutGlobalScopes()
                    ->where('value', $value)
                    ->whereHas('option', function (Builder $query) use ($product, $optionName): void {
                        $query
                            ->withoutGlobalScopes()
                            ->where('product_id', $product->getKey())
                            ->where('name', $optionName);
                    })
                    ->value('id');

                if ($valueId === null) {
                    throw new InvalidArgumentException("Variant option selection [{$optionName}: {$value}] is invalid for this product.");
                }

                return (int) $valueId;
            })
            ->values()
            ->all();
    }

    private function variantHasOrderLines(ProductVariant $variant): bool
    {
        return Schema::hasTable('order_lines')
            && DB::table('order_lines')->where('variant_id', $variant->getKey())->exists();
    }

    /**
     * @param  array<int, mixed>  $tags
     * @return list<string>
     */
    private function tagList(array $tags): array
    {
        return collect($tags)
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<int>
     */
    private function integerList(array $values): array
    {
        return collect($values)
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    private function nextMediaPosition(Product $product): int
    {
        $position = ProductMedia::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->max('position');

        return $position === null ? 0 : ((int) $position) + 1;
    }

    private function mediaTypeForContentType(string $contentType): MediaType
    {
        return $contentType === 'video/mp4' ? MediaType::Video : MediaType::Image;
    }

    private function extensionForContentType(string $contentType): string
    {
        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'video/mp4' => 'mp4',
            default => 'bin',
        };
    }

    /**
     * @return array{url: string, headers: array<string, string>}
     */
    private function temporaryUploadPayload(FilesystemAdapter $disk, string $storageKey, mixed $expiresAt): array
    {
        try {
            $payload = $disk->temporaryUploadUrl($storageKey, $expiresAt);

            return [
                'url' => (string) $payload['url'],
                'headers' => $payload['headers'] ?? [],
            ];
        } catch (Throwable) {
            return [
                'url' => $disk->url($storageKey),
                'headers' => [],
            ];
        }
    }

    private function throwProductValidationException(Throwable $exception): never
    {
        throw ValidationException::withMessages([
            'product' => [$exception->getMessage()],
        ]);
    }
}
