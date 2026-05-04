<?php

namespace App\Livewire\Admin\Products;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    /**
     * @var array<int, int>
     */
    public array $collectionIds = [];

    /**
     * @var array<int, array{name: string, values: string}>
     */
    public array $options = [];

    /**
     * @var array<int, array{id: int|null, label: string, sku: string, price: string, compareAtPrice: string, quantity: int, requiresShipping: bool}>
     */
    public array $variants = [];

    /**
     * @var array<int, array{id: int, url: string, exists: bool, altText: string, position: int, status: string}>
     */
    public array $media = [];

    /**
     * @var array<int, mixed>
     */
    public array $newMedia = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $store = app('current_store');

            abort_unless($store instanceof Store && (int) $product->store_id === $store->getKey(), 404);

            $this->product = $product->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'collections', 'media']);
            $this->fillFromProduct($this->product);

            return;
        }

        $this->variants = [[
            'id' => null,
            'label' => 'Default',
            'sku' => '',
            'price' => '0.00',
            'compareAtPrice' => '',
            'quantity' => 0,
            'requiresShipping' => true,
        ]];
    }

    public function updatedTitle(): void
    {
        if ($this->product === null && $this->handle === '') {
            $this->handle = Str::slug($this->title);
        }
    }

    public function addOption(): void
    {
        $this->options[] = [
            'name' => '',
            'values' => '',
        ];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function save(): void
    {
        $store = app('current_store');
        abort_unless($store instanceof Store, 404);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'handle')
                    ->where('store_id', $store->getKey())
                    ->ignore($this->product?->getKey()),
            ],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'numeric', 'min:0'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'variants.*.requiresShipping' => ['boolean'],
            'newMedia' => ['array', 'max:10'],
            'newMedia.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        if (! $this->validateActiveVariantPricing() || ! $this->validateVariantSkus($store)) {
            return;
        }

        $product = DB::transaction(function () use ($store): Product {
            $productService = app(ProductService::class);

            if ($this->product === null) {
                $product = $productService->create($store, [
                    ...$this->productPayload(),
                    'options' => $this->optionPayload(),
                    'variants' => $this->variantPayload($store),
                ]);
            } else {
                $payload = $this->productPayload();
                $requestedStatus = ProductStatus::from((string) $payload['status']);
                unset($payload['status']);

                $product = $productService->update($this->product, $payload);
                $this->syncExistingVariants($product, $store);

                if ($requestedStatus !== $product->refresh()->status) {
                    $productService->transitionStatus($product, $requestedStatus);
                }
            }

            $product->collections()->sync($this->collectionIds);

            return $product->refresh();
        });

        if ($this->newMedia !== []) {
            $this->storeNewMedia($product);
        }

        $this->product = $product->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'collections', 'media']);
        $this->fillFromProduct($this->product);

        session()->flash('status', 'Product saved');
        $this->dispatch('toast', type: 'success', message: __('Product saved'));
    }

    public function uploadMedia(): void
    {
        $product = $this->productForAction();

        $this->validate([
            'newMedia' => ['required', 'array', 'max:10'],
            'newMedia.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $this->storeNewMedia($product);

        $this->product = $product->refresh()->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'collections', 'media']);
        $this->fillFromProduct($this->product);

        session()->flash('status', 'Media uploaded');
        $this->dispatch('toast', type: 'success', message: __('Media uploaded'));
    }

    public function updateMediaAlt(int $mediaId): void
    {
        $index = $this->mediaIndex($mediaId);

        $this->validate([
            "media.{$index}.altText" => ['nullable', 'string', 'max:255'],
        ]);

        $media = $this->mediaRecord($mediaId);
        $altText = trim((string) $this->media[$index]['altText']);

        $media->forceFill([
            'alt_text' => $altText === '' ? null : $altText,
        ])->save();

        $this->refreshProductMedia();

        session()->flash('status', 'Media updated');
    }

    public function moveMedia(int $mediaId, string $direction): void
    {
        $ids = collect($this->media)->pluck('id')->map(fn (int $id): int => $id)->all();
        $index = array_search($mediaId, $ids, true);

        if ($index === false) {
            return;
        }

        $swapIndex = match ($direction) {
            'up' => $index - 1,
            'down' => $index + 1,
            default => $index,
        };

        if (! isset($ids[$swapIndex])) {
            return;
        }

        [$ids[$index], $ids[$swapIndex]] = [$ids[$swapIndex], $ids[$index]];

        $this->reorderMedia($ids);
    }

    /**
     * @param  array<int, int>  $order
     */
    public function reorderMedia(array $order): void
    {
        $product = $this->productForAction();
        $validIds = ProductMedia::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->whereIn('id', $order)
            ->pluck('id')
            ->all();

        $orderedIds = collect($order)
            ->map(fn (mixed $mediaId): int => (int) $mediaId)
            ->intersect($validIds)
            ->values();

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $position => $mediaId) {
                ProductMedia::withoutGlobalScopes()
                    ->whereKey($mediaId)
                    ->update(['position' => $position]);
            }
        });

        $this->refreshProductMedia();
    }

    public function removeMedia(int $mediaId): void
    {
        $media = $this->mediaRecord($mediaId);

        $media->delete();

        $this->reorderMedia(
            collect($this->media)
                ->pluck('id')
                ->reject(fn (int $id): bool => $id === $mediaId)
                ->values()
                ->all(),
        );

        session()->flash('status', 'Media removed');
    }

    public function deleteProduct(): void
    {
        abort_unless($this->product instanceof Product, 404);

        app(ProductService::class)->transitionStatus($this->product, ProductStatus::Archived);

        session()->flash('status', 'Product saved');
        $this->redirectRoute('admin.products.index', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.form', [
            'availableCollections' => Collection::query()->orderBy('title')->get(),
            'isEditing' => $this->product !== null,
        ])->layout('layouts.app', [
            'title' => $this->product ? $this->product->title : __('Add product'),
        ]);
    }

    private function fillFromProduct(Product $product): void
    {
        $this->title = $product->title;
        $this->descriptionHtml = (string) $product->description_html;
        $this->status = $product->status->value;
        $this->vendor = (string) $product->vendor;
        $this->productType = (string) $product->product_type;
        $this->tags = implode(', ', $product->tags ?? []);
        $this->handle = $product->handle;
        $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
        $this->collectionIds = $product->collections->pluck('id')->map(fn (int $id): int => $id)->all();
        $this->media = $product->media
            ->map(fn (ProductMedia $media): array => [
                'id' => $media->getKey(),
                'url' => Storage::disk('public')->url($media->storage_key),
                'exists' => Storage::disk('public')->exists($media->storage_key),
                'altText' => (string) $media->alt_text,
                'position' => $media->position,
                'status' => $media->status->value,
            ])
            ->values()
            ->all();
        $this->options = $product->options
            ->map(fn (ProductOption $option): array => [
                'name' => $option->name,
                'values' => $option->values->pluck('value')->implode(', '),
            ])
            ->all();
        $this->variants = $product->variants
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->getKey(),
                'label' => $variant->optionValues->isEmpty()
                    ? 'Default'
                    : $variant->optionValues->map(fn (ProductOptionValue $value): string => $value->value)->implode(' / '),
                'sku' => (string) $variant->sku,
                'price' => number_format($variant->price_amount / 100, 2, '.', ''),
                'compareAtPrice' => $variant->compare_at_amount ? number_format($variant->compare_at_amount / 100, 2, '.', '') : '',
                'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                'requiresShipping' => $variant->requires_shipping,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(): array
    {
        return [
            'title' => $this->title,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->productType ?: null,
            'tags' => collect(explode(',', $this->tags))
                ->map(fn (string $tag): string => trim($tag))
                ->filter()
                ->values()
                ->all(),
            'handle' => Str::slug($this->handle),
        ];
    }

    /**
     * @return array<int, array{name: string, position: int, values: array<int, array{value: string, position: int}>}>
     */
    private function optionPayload(): array
    {
        return collect($this->options)
            ->map(function (array $option, int $position): array {
                return [
                    'name' => $option['name'],
                    'position' => $position,
                    'values' => collect(explode(',', $option['values']))
                        ->map(fn (string $value): string => trim($value))
                        ->filter()
                        ->values()
                        ->map(fn (string $value, int $valuePosition): array => [
                            'value' => $value,
                            'position' => $valuePosition,
                        ])
                        ->all(),
                ];
            })
            ->filter(fn (array $option): bool => $option['name'] !== '' && $option['values'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function variantPayload(Store $store): array
    {
        return collect($this->variants)
            ->map(fn (array $variant, int $position): array => [
                'sku' => $variant['sku'] ?: null,
                'price_amount' => Money::fromDecimalString($variant['price']),
                'compare_at_amount' => $variant['compareAtPrice'] === '' ? null : Money::fromDecimalString($variant['compareAtPrice']),
                'currency' => $store->default_currency,
                'quantity_on_hand' => (int) $variant['quantity'],
                'requires_shipping' => (bool) $variant['requiresShipping'],
                'is_default' => $position === 0,
                'position' => $position,
            ])
            ->all();
    }

    private function syncExistingVariants(Product $product, Store $store): void
    {
        foreach ($this->variantPayload($store) as $position => $variantData) {
            $variantId = $this->variants[$position]['id'] ?? null;
            $variant = $variantId
                ? ProductVariant::withoutGlobalScopes()->where('product_id', $product->getKey())->findOrFail($variantId)
                : $product->variants()->create(['position' => $position]);

            $variant->forceFill([
                'sku' => $variantData['sku'],
                'price_amount' => $variantData['price_amount'],
                'compare_at_amount' => $variantData['compare_at_amount'],
                'currency' => $variantData['currency'],
                'requires_shipping' => $variantData['requires_shipping'],
                'is_default' => $position === 0,
                'position' => $position,
            ])->save();

            InventoryItem::withoutGlobalScopes()->updateOrCreate(
                ['variant_id' => $variant->getKey()],
                [
                    'store_id' => $store->getKey(),
                    'quantity_on_hand' => $variantData['quantity_on_hand'],
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ],
            );
        }
    }

    private function validateActiveVariantPricing(): bool
    {
        if ($this->status !== ProductStatus::Active->value) {
            return true;
        }

        $hasPricedVariant = collect($this->variants)
            ->contains(fn (array $variant): bool => Money::fromDecimalString($variant['price']) > 0);

        if ($hasPricedVariant) {
            return true;
        }

        $this->addError('variants.0.price', __('At least one priced variant is required before activation.'));

        return false;
    }

    private function validateVariantSkus(Store $store): bool
    {
        $skus = collect($this->variants)
            ->pluck('sku')
            ->map(fn (?string $sku): string => trim((string) $sku))
            ->filter()
            ->values();

        if ($skus->isEmpty()) {
            return true;
        }

        if ($skus->duplicates()->isNotEmpty()) {
            $this->addError('variants.0.sku', __('Each variant SKU must be unique for this store.'));

            return false;
        }

        $variantIds = collect($this->variants)
            ->pluck('id')
            ->filter()
            ->map(fn (mixed $variantId): int => (int) $variantId)
            ->values()
            ->all();

        $conflictingSku = ProductVariant::withoutGlobalScopes()
            ->whereIn('sku', $skus->all())
            ->whereHas('product', function ($query) use ($store): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey());
            })
            ->when($variantIds !== [], fn ($query) => $query->whereKeyNot($variantIds))
            ->value('sku');

        if ($conflictingSku === null) {
            return true;
        }

        $this->addError('variants.0.sku', __('The SKU [:sku] is already used in this store.', ['sku' => $conflictingSku]));

        return false;
    }

    private function storeNewMedia(Product $product): void
    {
        $maxPosition = ProductMedia::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->max('position');
        $position = $maxPosition === null ? 0 : ((int) $maxPosition) + 1;

        foreach ($this->newMedia as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $storageKey = $file->storeAs(
                "media/originals/{$product->getKey()}",
                Str::uuid().'.'.$extension,
                'public',
            );

            $media = ProductMedia::withoutGlobalScopes()->create([
                'product_id' => $product->getKey(),
                'type' => MediaType::Image,
                'storage_key' => $storageKey,
                'alt_text' => $product->title,
                'position' => $position++,
                'status' => MediaStatus::Processing,
            ]);

            ProcessMediaUpload::dispatch($media->getKey(), (int) $product->store_id);
        }

        $this->reset('newMedia');
    }

    private function productForAction(): Product
    {
        $store = app('current_store');

        abort_unless($store instanceof Store && $this->product instanceof Product, 404);

        return Product::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->findOrFail($this->product->getKey());
    }

    private function mediaRecord(int $mediaId): ProductMedia
    {
        return ProductMedia::withoutGlobalScopes()
            ->where('product_id', $this->productForAction()->getKey())
            ->findOrFail($mediaId);
    }

    private function mediaIndex(int $mediaId): int
    {
        $index = collect($this->media)->search(fn (array $media): bool => $media['id'] === $mediaId);

        abort_if($index === false, 404);

        return (int) $index;
    }

    private function refreshProductMedia(): void
    {
        $this->product = $this->productForAction()->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'collections', 'media']);
        $this->fillFromProduct($this->product);
    }
}
