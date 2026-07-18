<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    /** @var array<string, string> */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public bool $addedToCart = false;

    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->with([
                'options.values',
                'variants.inventoryItem',
                'variants.optionValues.option',
                'media',
                'collections',
            ])
            ->firstOr(fn () => abort(404));

        $defaultVariant = $this->product->variants->firstWhere('is_default', true)
            ?? $this->product->variants->first();

        if ($defaultVariant) {
            foreach ($defaultVariant->optionValues as $value) {
                $this->selectedOptions[$value->option->name] = $value->value;
            }
        }
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->product->variants->first(function (ProductVariant $variant): bool {
            $variantOptions = $variant->optionValues->mapWithKeys(
                fn ($value) => [$value->option->name => $value->value]
            );

            return $variantOptions->all() === $this->selectedOptions;
        });
    }

    #[Computed]
    public function availableQuantity(): ?int
    {
        $inventory = $this->selectedVariant?->inventoryItem;

        if (! $inventory) {
            return 0;
        }

        return $inventory->policy->value === 'continue' ? null : $inventory->availableQuantity();
    }

    public function selectOption(string $optionName, string $value): void
    {
        $this->selectedOptions[$optionName] = $value;
        $this->quantity = 1;
        $this->addedToCart = false;
        unset($this->selectedVariant, $this->availableQuantity);

        $this->dispatch('variant-changed', variantId: $this->selectedVariant?->id, price: $this->selectedVariant?->price_amount);
    }

    public function addToCart(): void
    {
        $variant = $this->selectedVariant;

        if (! $variant) {
            $this->addError('variant', 'Please select all product options.');

            return;
        }

        $cart = app(CartService::class)->getOrCreateForSession(app('current_store'), auth('customer')->user());

        try {
            app(CartService::class)->addLine($cart, $variant->id, $this->quantity);
        } catch (InsufficientInventoryException) {
            $this->addError('quantity', 'Not enough stock available.');

            return;
        }

        $this->addedToCart = true;
        $this->dispatch('cart-updated', cartId: $cart->id, itemCount: (int) $cart->lines()->sum('quantity'));
        $this->dispatch('open-cart-drawer');
    }

    /** @return Collection<int, array{name: string, position: int, values: array<int, string>}> */
    #[Computed]
    public function optionGroups(): Collection
    {
        return $this->product->options->sortBy('position')->map(fn ($option): array => [
            'name' => $option->name,
            'values' => $option->values->sortBy('position')->pluck('value')->all(),
        ]);
    }

    public function render()
    {
        return view('livewire.storefront.products.show')
            ->layout('layouts.storefront')
            ->title($this->product->title.' - '.app('current_store')->name);
    }
}
