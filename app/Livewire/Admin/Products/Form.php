<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Events\ProductUpdated;
use App\Jobs\ProcessMediaUpload;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $productId = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $vendor = '';

    public string $productType = '';

    public int $priceAmount = 0;

    public ?int $compareAtAmount = null;

    public int $quantityOnHand = 0;

    public string $sku = '';

    public string $status = 'draft';

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var array<int, array{id: int, title: string, sku: ?string, price_amount: int, compare_at_amount: ?int, quantity_on_hand: int, quantity_reserved: int, inventory_policy: string, requires_shipping: bool}> */
    public array $variants = [];

    /** @var list<int|string> */
    public array $collectionIds = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    public function mount(?int $product = null): void
    {
        if ($product) {
            $saved = Product::query()->with(['variants.inventoryItem', 'options.values', 'collections'])->findOrFail($product);
            Gate::authorize('update', $saved);
            $variant = $saved->variants->firstWhere('is_default', true) ?? $saved->variants->first();
            $this->productId = $saved->id;
            $this->title = $saved->title;
            $this->handle = $saved->handle;
            $this->descriptionHtml = app(HtmlSanitizer::class)->sanitize($saved->description_html);
            $this->vendor = $saved->vendor ?? '';
            $this->productType = $saved->product_type ?? '';
            $this->status = $saved->status;
            $this->priceAmount = (int) ($variant?->price_amount ?? 0);
            $this->compareAtAmount = $variant?->compare_at_amount;
            $this->quantityOnHand = (int) ($variant?->inventoryItem?->quantity_on_hand ?? 0);
            $this->sku = $variant?->sku ?? '';
            $this->options = $saved->options->map(fn ($option): array => [
                'name' => $option->name,
                'values' => $option->values->pluck('value')->implode(', '),
            ])->values()->all();
            $this->variants = $saved->variants->where('status', 'active')->map(fn (ProductVariant $savedVariant): array => [
                'id' => $savedVariant->id,
                'title' => $savedVariant->title ?: 'Default',
                'sku' => $savedVariant->sku,
                'price_amount' => (int) $savedVariant->price_amount,
                'compare_at_amount' => $savedVariant->compare_at_amount,
                'quantity_on_hand' => (int) ($savedVariant->inventoryItem?->quantity_on_hand ?? 0),
                'quantity_reserved' => (int) ($savedVariant->inventoryItem?->quantity_reserved ?? 0),
                'inventory_policy' => $savedVariant->inventoryItem?->policy ?? 'deny',
                'requires_shipping' => (bool) $savedVariant->requires_shipping,
            ])->values()->all();
            $this->collectionIds = $saved->collections->modelKeys();
        } else {
            Gate::authorize('create', Product::class);
        }
    }

    public function addOption(): void
    {
        if (count($this->options) >= 3) {
            $this->addError('options', 'A product can have at most three options.');

            return;
        }

        $this->options[] = ['name' => '', 'values' => ''];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        $this->resetValidation('options');
    }

    public function save(ProductService $products, VariantMatrixService $matrix): mixed
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'priceAmount' => ['required', 'integer', 'min:0'],
            'compareAtAmount' => ['nullable', 'integer', 'min:0'],
            'quantityOnHand' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:draft,active,archived'],
            'options' => ['array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.values' => ['required', 'string', 'max:2000'],
            'variants' => ['array'],
            'variants.*.id' => ['required', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price_amount' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.quantity_on_hand' => ['required', 'integer', 'min:0'],
            'variants.*.inventory_policy' => ['required', 'in:deny,continue'],
            'variants.*.requires_shipping' => ['boolean'],
            'collectionIds' => ['array'],
            'collectionIds.*' => ['integer', 'distinct'],
            'uploads' => ['array', 'max:20'],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:'.(int) ceil(config('shop.media.image_max_bytes', 10485760) / 1024)],
        ]);

        if ($this->compareAtAmount !== null && $this->compareAtAmount <= $this->priceAmount) {
            throw ValidationException::withMessages(['compareAtAmount' => 'The compare-at price must be greater than the variant price.']);
        }

        foreach ($this->variants as $index => $variant) {
            $compareAtAmount = $variant['compare_at_amount'] ?? null;

            if ($compareAtAmount !== null && $compareAtAmount !== '' && (int) $compareAtAmount <= (int) $variant['price_amount']) {
                throw ValidationException::withMessages(["variants.{$index}.compare_at_amount" => 'The compare-at price must be greater than the variant price.']);
            }
        }

        $optionMatrix = $this->optionMatrix();
        $initialProductState = null;
        $initialStatus = null;

        $product = DB::transaction(function () use ($products, $matrix, $optionMatrix, &$initialProductState, &$initialStatus): Product {

            if ($this->productId) {
                $product = Product::query()->findOrFail($this->productId);
                Gate::authorize('update', $product);
                $initialProductState = $this->productState($product);
                $initialStatus = $product->status;
                $product = $products->update($product, [
                    'title' => $this->title,
                    'handle' => $this->handle,
                    'description_html' => app(HtmlSanitizer::class)->sanitize($this->descriptionHtml),
                    'vendor' => $this->vendor,
                    'product_type' => $this->productType,
                ], false);
            } else {
                $store = app('current_store');
                abort_unless($store instanceof Store, 404);
                $product = $products->create($store, [
                    'title' => $this->title,
                    'handle' => $this->handle ?: $this->title,
                    'description_html' => app(HtmlSanitizer::class)->sanitize($this->descriptionHtml),
                    'vendor' => $this->vendor ?: null,
                    'product_type' => $this->productType ?: null,
                    'status' => 'draft',
                    'variants' => [[
                        'sku' => $this->sku ?: null,
                        'price_amount' => $this->priceAmount,
                        'compare_at_amount' => $this->compareAtAmount,
                        'quantity_on_hand' => $this->quantityOnHand,
                        'is_default' => true,
                        'requires_shipping' => true,
                    ]],
                ]);
            }

            $defaultVariant = $product->variants()->where('is_default', true)->first() ?? $product->variants()->firstOrFail();
            $this->assertSkuAvailable($this->sku, $product, $defaultVariant->id, 'sku');
            $reservedQuantity = (int) ($defaultVariant->inventoryItem?->quantity_reserved ?? 0);

            if ($this->quantityOnHand < $reservedQuantity) {
                throw ValidationException::withMessages(['quantityOnHand' => 'Available quantity cannot be lower than quantity reserved for active checkouts.']);
            }

            $defaultVariant->forceFill([
                'sku' => $this->sku ?: null,
                'price_amount' => $this->priceAmount,
                'compare_at_amount' => $this->compareAtAmount,
            ])->save();
            $defaultVariant->inventoryItem()->updateOrCreate([], [
                'store_id' => $product->store_id,
                'quantity_on_hand' => $this->quantityOnHand,
            ]);

            $rebuiltVariants = $matrix->rebuild($product, $optionMatrix);
            $isCreatingMatrix = ! $this->productId && $optionMatrix !== [];

            foreach ($rebuiltVariants as $rebuiltVariant) {
                $submitted = collect($this->variants)->firstWhere('id', $rebuiltVariant->id);

                if ($submitted === null) {
                    if ($isCreatingMatrix && $rebuiltVariant->is_default) {
                        $this->assertSkuAvailable($this->sku, $product, $rebuiltVariant->id, 'sku');
                        $rebuiltVariant->forceFill(['sku' => $this->sku ?: null])->save();
                        $rebuiltVariant->inventoryItem()->updateOrCreate([], [
                            'store_id' => $product->store_id,
                            'quantity_on_hand' => $this->quantityOnHand,
                            'policy' => 'deny',
                        ]);
                    }

                    continue;
                }

                $this->assertSkuAvailable($submitted['sku'] ?? '', $product, $rebuiltVariant->id, 'variants');
                $inventory = $rebuiltVariant->inventoryItem;
                $reserved = (int) ($inventory?->quantity_reserved ?? 0);

                if ((int) $submitted['quantity_on_hand'] < $reserved) {
                    throw ValidationException::withMessages(['variants' => 'Available quantity cannot be lower than quantity reserved for active checkouts.']);
                }

                $rebuiltVariant->forceFill([
                    'sku' => filled($submitted['sku'] ?? null) ? trim((string) $submitted['sku']) : null,
                    'price_amount' => (int) $submitted['price_amount'],
                    'compare_at_amount' => $submitted['compare_at_amount'] === '' ? null : ($submitted['compare_at_amount'] ?? null),
                    'requires_shipping' => (bool) ($submitted['requires_shipping'] ?? false),
                ])->save();
                $rebuiltVariant->inventoryItem()->updateOrCreate([], [
                    'store_id' => $product->store_id,
                    'quantity_on_hand' => (int) $submitted['quantity_on_hand'],
                    'policy' => $submitted['inventory_policy'],
                ]);
            }

            $ownedCollectionIds = Collection::query()->whereIn('id', array_map('intval', $this->collectionIds))->pluck('id')->all();

            if (count($ownedCollectionIds) !== count(array_unique(array_map('intval', $this->collectionIds)))) {
                throw ValidationException::withMessages(['collectionIds' => 'Choose collections belonging to this store.']);
            }

            $product->collections()->sync($ownedCollectionIds);

            if ($product->status !== $this->status) {
                Gate::authorize($this->status === 'archived' ? 'archive-products' : 'manage-products');
                $products->transitionStatus($product, ProductStatus::from($this->status), false);
            }

            return $product;
        });

        if ($initialProductState !== null && $initialStatus !== null) {
            $product = $product->refresh();

            if ($initialProductState !== $this->productState($product)) {
                if ($initialStatus !== $product->status) {
                    ProductStatusChanged::dispatch($product, $initialStatus, $product->status);
                } else {
                    ProductUpdated::dispatch($product);
                }
            }
        }

        $this->storeUploads($product);
        session()->flash('status', 'Product saved.');

        return $this->redirectRoute('admin.products.edit', ['product' => $product->id]);
    }

    public function uploadMedia(): void
    {
        $product = $this->authorizedProduct();
        $this->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:20'],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:'.(int) ceil(config('shop.media.image_max_bytes', 10485760) / 1024)],
        ]);

        $this->storeUploads($product);
        session()->flash('status', 'Images uploaded and queued for processing.');
    }

    public function updateMediaAlt(int $mediaId, string $altText): void
    {
        $product = $this->authorizedProduct();
        validator(['alt_text' => $altText], ['alt_text' => ['nullable', 'string', 'max:255']])->validate();
        $product->media()->whereKey($mediaId)->firstOrFail()->update(['alt_text' => filled($altText) ? $altText : null]);
    }

    public function moveMedia(int $mediaId, int $direction): void
    {
        abort_unless(in_array($direction, [-1, 1], true), 422);
        $product = $this->authorizedProduct();
        $items = $product->media()->orderBy('position')->orderBy('id')->get();
        $index = $items->search(fn (ProductMedia $media): bool => $media->id === $mediaId);

        if ($index === false) {
            abort(404);
        }

        $targetIndex = $index + $direction;

        if (! $items->has($targetIndex)) {
            return;
        }

        DB::transaction(function () use ($items, $index, $targetIndex): void {
            $first = $items[$index];
            $second = $items[$targetIndex];
            $firstPosition = $first->position;
            $first->update(['position' => $second->position]);
            $second->update(['position' => $firstPosition]);
        });
    }

    public function deleteMedia(int $mediaId): void
    {
        $product = $this->authorizedProduct();
        $product->media()->whereKey($mediaId)->firstOrFail()->delete();
        $product->media()->orderBy('position')->orderBy('id')->get()->each(function (ProductMedia $media, int $position): void {
            if ($media->position !== $position) {
                $media->update(['position' => $position]);
            }
        });
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.form', [
            'isEditing' => $this->productId !== null,
            'collections' => Collection::query()->orderBy('title')->get(['id', 'title']),
            'hasOptionMatrix' => $this->options !== [],
            'mediaItems' => $this->productId ? Product::query()->findOrFail($this->productId)->media : collect(),
        ])->layout('layouts.admin');
    }

    private function authorizedProduct(): Product
    {
        abort_if($this->productId === null, 422, 'Save the product before managing its media.');
        $product = Product::query()->findOrFail($this->productId);
        Gate::authorize('update', $product);

        return $product;
    }

    /** @return array<string, mixed> */
    private function productState(Product $product): array
    {
        $product->refresh();

        return [
            'product' => $product->only(['title', 'handle', 'description_html', 'vendor', 'product_type', 'tags', 'status', 'published_at']),
            'variants' => $product->variants()
                ->with(['inventoryItem', 'optionValues'])
                ->orderBy('id')
                ->get()
                ->map(static fn (ProductVariant $variant): array => [
                    'id' => (int) $variant->getKey(),
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'price_amount' => (int) $variant->price_amount,
                    'compare_at_amount' => $variant->compare_at_amount,
                    'currency' => $variant->currency,
                    'weight_g' => $variant->weight_g,
                    'requires_shipping' => (bool) $variant->requires_shipping,
                    'is_default' => (bool) $variant->is_default,
                    'position' => (int) $variant->position,
                    'status' => $variant->status,
                    'inventory' => $variant->inventoryItem?->only(['quantity_on_hand', 'quantity_reserved', 'policy']),
                    'option_value_ids' => $variant->optionValues->modelKeys(),
                ])
                ->all(),
            'options' => $product->options()
                ->with('values')
                ->orderBy('position')
                ->get()
                ->map(static fn ($option): array => [
                    'name' => $option->name,
                    'values' => $option->values->sortBy('position')->pluck('value')->values()->all(),
                ])
                ->all(),
            'collection_ids' => $product->collections()->orderBy('collections.id')->pluck('collections.id')->map(static fn ($id): int => (int) $id)->all(),
        ];
    }

    private function storeUploads(Product $product): void
    {
        foreach ($this->uploads as $upload) {
            $extensionByMime = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/avif' => 'avif',
            ];
            $mimeType = $upload->getMimeType();
            $extension = $extensionByMime[$mimeType] ?? null;

            if ($extension === null) {
                throw ValidationException::withMessages(['uploads' => 'Upload a JPEG, PNG, WebP, or AVIF image.']);
            }

            $storageKey = "stores/{$product->store_id}/products/{$product->id}/media/".Str::uuid().".{$extension}";
            $storedKey = $upload->storePubliclyAs(dirname($storageKey), basename($storageKey), 'public');

            if ($storedKey !== $storageKey) {
                throw new \RuntimeException('The product image could not be stored.');
            }

            try {
                $media = $product->media()->create([
                    'type' => 'image',
                    'storage_key' => $storageKey,
                    'mime_type' => $mimeType,
                    'byte_size' => (int) $upload->getSize(),
                    'position' => (int) $product->media()->max('position') + 1,
                    'status' => 'processing',
                    'created_at' => now(),
                ]);
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete($storageKey);
                throw $exception;
            }

            ProcessMediaUpload::dispatch($media->id)->onConnection('database');
        }

        $this->reset('uploads');
    }

    /** @return array<int, array{name: string, values: list<string>}> */
    private function optionMatrix(): array
    {
        $normalized = [];
        $seenNames = [];

        foreach ($this->options as $position => $option) {
            $name = trim((string) ($option['name'] ?? ''));
            $nameKey = mb_strtolower($name);

            if ($name === '' || isset($seenNames[$nameKey])) {
                throw ValidationException::withMessages(["options.{$position}.name" => 'Option names must be present and unique.']);
            }

            $seenNames[$nameKey] = true;
            $values = array_map('trim', explode(',', (string) ($option['values'] ?? '')));
            $seenValues = [];

            foreach ($values as $valuePosition => $value) {
                $valueKey = mb_strtolower($value);

                if ($value === '' || isset($seenValues[$valueKey])) {
                    throw ValidationException::withMessages(["options.{$position}.values" => 'Option values must be present and unique.']);
                }

                $seenValues[$valueKey] = true;
            }

            $normalized[] = ['name' => $name, 'values' => $values];
        }

        return $normalized;
    }

    private function assertSkuAvailable(?string $sku, Product $product, int $ignoreVariantId, string $field): void
    {
        $normalizedSku = trim((string) $sku);

        if ($normalizedSku === '') {
            return;
        }

        $exists = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('products.store_id', $product->store_id)
            ->where('product_variants.sku', $normalizedSku)
            ->where('product_variants.id', '!=', $ignoreVariantId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([$field => 'This SKU is already in use in this store.']);
        }
    }
}
