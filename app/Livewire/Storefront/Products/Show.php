<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    /**
     * Currently selected option values keyed by option name.
     *
     * @var array<string, string|null>
     */
    public array $selectedOptions = [];

    public int $quantity = 1;

    /**
     * Resolve the product by handle; only visible products are reachable.
     */
    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->visible()
            ->where('handle', $handle)
            ->with(['media', 'options.values', 'variants.optionValues', 'variants.inventoryItem', 'collections'])
            ->firstOrFail();

        // Preselect the default variant's option values.
        $default = $this->product->variants->firstWhere('is_default', true) ?? $this->product->variants->first();

        foreach ($this->product->options as $option) {
            $this->selectedOptions[$option->name] = $default?->optionValues
                ->firstWhere('product_option_id', $option->id)?->value;
        }
    }

    /**
     * The variant matching all currently selected options, if any.
     */
    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->product->variants->first(function (ProductVariant $variant): bool {
            foreach ($this->product->options as $option) {
                $selected = $this->selectedOptions[$option->name] ?? null;
                $variantValue = $variant->optionValues->firstWhere('product_option_id', $option->id)?->value;

                if ($selected !== $variantValue) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Whether a value of the given option leads to a purchasable variant
     * given the other current selections.
     */
    public function isValueAvailable(ProductOption $option, string $value): bool
    {
        return $this->product->variants->contains(function (ProductVariant $variant) use ($option, $value): bool {
            $matches = $variant->optionValues->firstWhere('product_option_id', $option->id)?->value === $value;

            if (! $matches) {
                return false;
            }

            foreach ($this->product->options as $otherOption) {
                if ($otherOption->id === $option->id) {
                    continue;
                }

                $selected = $this->selectedOptions[$otherOption->name] ?? null;

                if ($selected !== null && $variant->optionValues->firstWhere('product_option_id', $otherOption->id)?->value !== $selected) {
                    return false;
                }
            }

            return $variant->isInStock() || $variant->isBackorderable();
        });
    }

    /**
     * Stock state of the selected variant for messaging and purchase rules.
     *
     * @return array{state: string, message: string, purchasable: bool, max: int|null}
     */
    public function stockState(): array
    {
        $variant = $this->selectedVariant();

        if ($variant === null) {
            return ['state' => 'unavailable', 'message' => 'Unavailable', 'purchasable' => false, 'max' => null];
        }

        $available = $variant->availableQuantity();

        if ($available > 10) {
            return ['state' => 'in_stock', 'message' => 'In stock', 'purchasable' => true, 'max' => $this->maxQuantity($variant, $available)];
        }

        if ($available > 0) {
            return ['state' => 'low_stock', 'message' => "Only {$available} left in stock", 'purchasable' => true, 'max' => $this->maxQuantity($variant, $available)];
        }

        if ($variant->inventoryItem?->policy === InventoryPolicy::Continue) {
            return ['state' => 'backorder', 'message' => 'Available on backorder', 'purchasable' => true, 'max' => null];
        }

        return ['state' => 'out_of_stock', 'message' => 'Out of stock', 'purchasable' => false, 'max' => null];
    }

    /**
     * Select an option value and keep the quantity within bounds.
     */
    public function selectOption(string $optionName, string $value): void
    {
        $this->selectedOptions[$optionName] = $value;
        $this->clampQuantity();
    }

    /**
     * Add the selected variant to the session cart, update the header
     * badge and open the cart drawer.
     */
    public function addToCart(): void
    {
        $variant = $this->selectedVariant();

        if ($variant === null || (! $variant->isInStock() && ! $variant->isBackorderable())) {
            return;
        }

        $carts = app(\App\Services\CartService::class);
        $cart = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user());

        try {
            $carts->addLine($cart, $variant->id, max(1, $this->quantity));
        } catch (\App\Exceptions\InsufficientInventoryException|\Illuminate\Validation\ValidationException) {
            return;
        }

        $this->dispatch('cart-updated', count: $cart->refresh()->itemCount());
        $this->dispatch('cart-drawer-open');
    }

    /**
     * Render the product page.
     */
    public function render(): View
    {
        $variant = $this->selectedVariant();
        $storeName = app('current_store')->name;
        $currency = $variant?->currency ?? $this->product->variants->first()?->currency
            ?? app('current_store')->default_currency;
        $primaryImage = $this->product->media->first();
        $metaDescription = str()->limit(trim(strip_tags($this->product->description_html ?? '')), 160, '');

        return view('livewire.storefront.products.show', [
            'currency' => $currency,
            'stock' => $this->stockState(),
            'jsonLd' => $this->jsonLd($variant, $primaryImage?->url()),
        ])
            ->layout('storefront.layouts.app', [
                'metaDescription' => $metaDescription,
                'og' => array_filter([
                    'title' => $this->product->title,
                    'description' => $metaDescription,
                    'image' => $primaryImage?->url(),
                    'type' => 'product',
                    'price_amount' => $variant !== null ? number_format($variant->price_amount / 100, 2, '.', '') : null,
                    'price_currency' => $currency,
                ]),
            ])
            ->title("{$this->product->title} - {$storeName}");
    }

    /**
     * JSON-LD structured data for the product (spec 04 §19).
     *
     * @return array<string, mixed>
     */
    private function jsonLd(?ProductVariant $variant, ?string $imageUrl): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->product->title,
            'description' => trim(strip_tags($this->product->description_html ?? '')),
            'image' => $imageUrl,
            'brand' => $this->product->vendor !== null ? ['@type' => 'Brand', 'name' => $this->product->vendor] : null,
            'offers' => $variant !== null ? [
                '@type' => 'Offer',
                'price' => number_format($variant->price_amount / 100, 2, '.', ''),
                'priceCurrency' => $variant->currency,
                'availability' => $variant->isInStock() || $variant->isBackorderable()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ] : null,
        ]);
    }

    /**
     * Maximum selectable quantity for the variant.
     */
    private function maxQuantity(ProductVariant $variant, int $available): ?int
    {
        return $variant->inventoryItem?->policy === InventoryPolicy::Deny ? $available : null;
    }

    /**
     * Keep the quantity within the bounds of the selected variant.
     */
    private function clampQuantity(): void
    {
        $max = $this->stockState()['max'];

        if ($max !== null) {
            $this->quantity = min($this->quantity, $max);
        }

        $this->quantity = max(1, $this->quantity);
    }
}
