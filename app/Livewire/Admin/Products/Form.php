<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
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

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $store = app('current_store');

            abort_unless($store instanceof Store && (int) $product->store_id === $store->getKey(), 404);

            $this->product = $product->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'collections']);
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

        $this->product = $product->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'collections']);
        $this->fillFromProduct($this->product);

        session()->flash('status', 'Product saved');
        $this->dispatch('toast', type: 'success', message: __('Product saved'));
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
}
