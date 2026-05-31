<?php

namespace App\Livewire\Storefront\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Product detail page: image gallery, variant selector, price, stock messaging,
 * quantity, and add-to-cart.
 *
 * The actual cart mutation is delegated to the global {@see \App\Livewire\Storefront\CartDrawer}
 * via the `add-to-cart` browser event (which adds the line, opens the drawer,
 * and broadcasts `cart-updated`); this component owns only selection/display
 * state so the cart logic stays in one place.
 */
#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public Product $product;

    /**
     * Selected option value per option name, e.g. ['Size' => 'M', 'Color' => 'Red'].
     *
     * @var array<string, string>
     */
    public array $selectedOptions = [];

    public int $quantity = 1;

    /**
     * Resolve the product by handle within the current store (404 unless
     * published) and preselect the default variant's options.
     */
    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->published()
            ->with(['variants.optionValues.option', 'variants.inventoryItem', 'options.values', 'media'])
            ->where('handle', $handle)
            ->firstOrFail();

        $default = $this->product->primaryVariant();

        if ($default !== null) {
            $this->selectedOptions = $this->optionsForVariant($default);
        }
    }

    /**
     * Add the selected variant to the cart via the global cart drawer.
     */
    public function addToCart(): void
    {
        $variant = $this->selectedVariant();

        if ($variant === null || ! $this->variantIsPurchasable($variant)) {
            $this->dispatch('cart-error', message: __('This product is currently out of stock.'));

            return;
        }

        // The CartDrawer listens for add-to-cart, performs the mutation, opens
        // itself, and broadcasts cart-updated for the header badge.
        $this->dispatch('add-to-cart', variantId: $variant->id, quantity: max(1, $this->quantity));
    }

    public function render()
    {
        $variant = $this->selectedVariant();
        $currency = $this->product->variants->first()?->currency
            ?? (app()->bound('current_store') ? app('current_store')->default_currency : 'USD');

        return view('livewire.storefront.products.show', [
            'options' => $this->optionGroups(),
            'selectedVariant' => $variant,
            'currency' => $currency,
            'priceAmount' => $variant?->price_amount ?? $this->product->displayPriceAmount() ?? 0,
            'compareAtAmount' => $variant?->compare_at_amount ?? $this->product->compareAtAmount(),
            'media' => $this->product->media->sortBy('position')->values(),
            'stock' => $this->stockState($variant),
            'maxQuantity' => $this->maxQuantity($variant),
            'tags' => $this->product->tags ?? [],
        ]);
    }

    /**
     * Option groups for the selector: each option name with its ordered values.
     *
     * @return Collection<int, array{name: string, values: list<string>}>
     */
    private function optionGroups(): Collection
    {
        return $this->product->options
            ->sortBy('position')
            ->map(fn ($option): array => [
                'name' => $option->name,
                'values' => $option->values->sortBy('position')->pluck('value')->values()->all(),
            ])
            ->values();
    }

    /**
     * The variant matching every currently selected option, or null.
     */
    private function selectedVariant(): ?ProductVariant
    {
        if ($this->selectedOptions === []) {
            return $this->product->primaryVariant();
        }

        return $this->product->variants->first(function (ProductVariant $variant): bool {
            $variantOptions = $this->optionsForVariant($variant);

            foreach ($this->selectedOptions as $name => $value) {
                if (($variantOptions[$name] ?? null) !== $value) {
                    return false;
                }
            }

            return count($variantOptions) === count($this->selectedOptions);
        });
    }

    /**
     * The {option name => value} map for a variant.
     *
     * @return array<string, string>
     */
    private function optionsForVariant(ProductVariant $variant): array
    {
        return $variant->optionValues
            ->mapWithKeys(fn ($value): array => [$value->option->name => $value->value])
            ->all();
    }

    /**
     * Stock messaging state for the selected variant, per the storefront spec.
     *
     * @return array{label: string, tone: string, purchasable: bool}
     */
    private function stockState(?ProductVariant $variant): array
    {
        if ($variant === null) {
            return ['label' => __('Unavailable'), 'tone' => 'red', 'purchasable' => false];
        }

        $inventory = $variant->inventoryItem;
        $available = $inventory?->available() ?? 0;
        $continue = $inventory !== null && $inventory->policy->value === 'continue';

        if ($available > 10) {
            return ['label' => __('In stock'), 'tone' => 'green', 'purchasable' => true];
        }

        if ($available > 0) {
            return ['label' => __('Only :count left in stock', ['count' => $available]), 'tone' => 'amber', 'purchasable' => true];
        }

        if ($continue) {
            return ['label' => __('Available on backorder'), 'tone' => 'blue', 'purchasable' => true];
        }

        return ['label' => __('Out of stock'), 'tone' => 'red', 'purchasable' => false];
    }

    /**
     * Whether a variant can be purchased now (in stock or backorderable).
     */
    private function variantIsPurchasable(ProductVariant $variant): bool
    {
        $inventory = $variant->inventoryItem;

        if ($inventory === null) {
            return false;
        }

        return $inventory->available() > 0 || $inventory->policy->value === 'continue';
    }

    /**
     * The quantity cap for the selector: available stock under a deny policy,
     * otherwise unlimited (null).
     */
    private function maxQuantity(?ProductVariant $variant): ?int
    {
        $inventory = $variant?->inventoryItem;

        if ($inventory === null || $inventory->policy->value === 'continue') {
            return null;
        }

        return max(1, $inventory->available());
    }

    /**
     * Formatted meta description (stripped product description, truncated).
     */
    public function getMetaDescriptionProperty(): string
    {
        return str(strip_tags((string) $this->product->description_html))->squish()->limit(160)->value();
    }
}
