<?php

namespace App\Livewire\Storefront\Products;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Show extends StorefrontComponent
{
    public Product $product;

    /** @var array<string, string> */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public int $activeMedia = 0;

    public function mount(string|Product $handle): void
    {
        $this->product = ($handle instanceof Product
            ? $handle->newQuery()->whereKey($handle->getKey())
            : Product::query()->where('handle', $handle))
            ->where('store_id', $this->currentStore()->getKey())
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->with([
                'collections',
                'media' => fn ($query) => $query->where('status', 'ready')->orderBy('position'),
                'options' => fn ($query) => $query->with(['values' => fn ($valueQuery) => $valueQuery->orderBy('position')])->orderBy('position'),
                'variants' => fn ($query) => $query->where('status', 'active')->with(['inventoryItem', 'optionValues.option'])->orderBy('position'),
            ])
            ->firstOrFail();

        foreach ($this->product->options as $option) {
            $defaultVariant = $this->product->variants->firstWhere('is_default', true) ?: $this->product->variants->first();
            $value = $defaultVariant?->optionValues->first(fn ($value) => (int) $value->product_option_id === (int) $option->id);
            $this->selectedOptions[$option->name] = $value?->value ?? (string) optional($option->values->first())->value;
        }
    }

    public function updatedSelectedOptions(): void
    {
        $this->quantity = 1;
        unset($this->selectedVariant);
        $this->dispatch('variant-changed',
            variantId: $this->selectedVariant?->getKey(),
            price: $this->selectedVariant?->price_amount,
            stock: $this->availableQuantity,
        );
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        if ($this->product->options->isEmpty()) {
            return $this->product->variants->firstWhere('is_default', true) ?: $this->product->variants->first();
        }

        return $this->product->variants->first(function (ProductVariant $variant): bool {
            $values = $variant->optionValues->mapWithKeys(function ($value): array {
                $optionName = $value->option?->name;

                return $optionName ? [$optionName => $value->value] : [];
            })->all();

            foreach ($this->selectedOptions as $name => $selected) {
                if (($values[$name] ?? null) !== $selected) {
                    return false;
                }
            }

            return count($values) === count($this->selectedOptions);
        });
    }

    #[Computed]
    public function availableQuantity(): ?int
    {
        $inventory = $this->selectedVariant?->inventoryItem;

        if (! $inventory || $this->enumValue($inventory->policy) === 'continue') {
            return null;
        }

        return max(0, (int) $inventory->quantity_on_hand - (int) $inventory->quantity_reserved);
    }

    #[Computed]
    public function canAddToCart(): bool
    {
        return $this->selectedVariant !== null && ($this->availableQuantity === null || $this->availableQuantity > 0);
    }

    public function addToCart(): void
    {
        $variant = $this->selectedVariant;
        if (! $variant) {
            $this->addError('variant', 'Please choose all options.');

            return;
        }

        $max = $this->availableQuantity;
        $this->validate(['quantity' => ['required', 'integer', 'min:1', $max === null ? 'max:999' : 'max:'.$max]]);

        try {
            $cartService = app(CartService::class);
            $cart = $cartService->getOrCreateForSession($this->currentStore(), Auth::guard('customer')->user());
            $cartService->addLine($cart, $variant->getKey(), $this->quantity);
            $cart->load('lines');

            $this->dispatch('cart-updated', cartId: $cart->getKey(), itemCount: (int) $cart->lines->sum('quantity'));
            $this->dispatch('toast', type: 'success', message: 'Added to cart');
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('variant', str_contains(strtolower($exception->getMessage()), 'stock')
                ? 'This product is currently out of stock.'
                : 'We could not add this item. Please try again.');
        }
    }

    public function render(): View
    {
        $description = str(strip_tags((string) $this->product->description_html))->squish()->limit(160)->toString();

        return $this->storefront(
            view('storefront.products.show'),
            $this->product->title.' - '.$this->currentStore()->name,
            $description,
        );
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
