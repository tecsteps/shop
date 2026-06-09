<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ThemeSettingsService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Show extends Component
{
    public Product $product;

    /** @var array<string, string> Selected option values, keyed by option name. */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public int $activeImageIndex = 0;

    public bool $addedToCart = false;

    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->published()
            ->where('handle', $handle)
            ->with(['options.values', 'variants.optionValues', 'variants.inventoryItem', 'media'])
            ->firstOrFail();

        $initialVariant = $this->defaultVariant();

        foreach ($this->product->options as $option) {
            $value = $initialVariant?->optionValues->firstWhere('product_option_id', $option->getKey());

            $this->selectedOptions[$option->name] = $value->value
                ?? $option->values->first()?->value
                ?? '';
        }
    }

    public function updatedSelectedOptions(): void
    {
        $this->addedToCart = false;
        $this->quantity = 1;
        $this->activeImageIndex = 0;

        $variant = $this->selectedVariant();

        $this->dispatch(
            'variant-changed',
            variantId: $variant?->getKey(),
            price: $variant?->price_amount,
            stock: $variant?->inventoryItem?->availableQuantity(),
        );
    }

    /**
     * The variant matching every currently selected option value, or the
     * default variant for products without options.
     */
    public function selectedVariant(): ?ProductVariant
    {
        if ($this->product->options->isEmpty()) {
            return $this->defaultVariant();
        }

        return $this->product->variants->first(function (ProductVariant $variant): bool {
            foreach ($this->product->options as $option) {
                $variantValue = $variant->optionValues
                    ->firstWhere('product_option_id', $option->getKey())
                    ?->value;

                if ($variantValue !== ($this->selectedOptions[$option->name] ?? null)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Whether the selected variant can be purchased right now.
     */
    public function isPurchasable(): bool
    {
        $variant = $this->selectedVariant();

        if ($variant === null) {
            return false;
        }

        $inventory = $variant->inventoryItem;

        if ($inventory === null) {
            return true;
        }

        return $inventory->availableQuantity() > 0 || $inventory->policy === InventoryPolicy::Continue;
    }

    public function addToCart(): void
    {
        $variant = $this->selectedVariant();

        if ($variant === null || ! $this->isPurchasable()) {
            return;
        }

        $this->quantity = max(1, $this->quantity);

        /*
         * Phase 4 integration point: replace this stub with the CartService
         * (create or resolve the session cart, add a cart line for the
         * selected variant and quantity, and return the real item count).
         * The "cart-updated" browser event below is the contract the cart
         * drawer and header badge listen for.
         */
        $this->dispatch('cart-updated', variantId: $variant->getKey(), itemCount: $this->quantity);

        $this->addedToCart = true;
    }

    public function render(ThemeSettingsService $themeSettings): View
    {
        return view('livewire.storefront.products.show', [
            'selectedVariant' => $this->selectedVariant(),
            'isPurchasable' => $this->isPurchasable(),
            'settings' => $themeSettings->all(),
        ])->title($this->product->title);
    }

    protected function defaultVariant(): ?ProductVariant
    {
        return $this->product->variants->firstWhere('is_default', true)
            ?? $this->product->variants->first();
    }
}
