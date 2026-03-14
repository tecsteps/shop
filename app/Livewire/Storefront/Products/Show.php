<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    /** @var array<string, string> */
    public array $selectedOptions = [];

    public function mount(string $handle): void
    {
        $product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->with([
                'variants' => fn ($q) => $q->where('status', VariantStatus::Active)->orderBy('position'),
                'variants.inventoryItem',
                'variants.optionValues.option',
                'options' => fn ($q) => $q->orderBy('position'),
                'options.values' => fn ($q) => $q->orderBy('position'),
                'media' => fn ($q) => $q->orderBy('position'),
                'collections' => fn ($q) => $q->where('status', 'active')->limit(1),
            ])
            ->first();

        if (! $product) {
            abort(404);
        }

        $this->product = $product;

        $defaultVariant = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        if ($defaultVariant) {
            $this->selectedVariantId = $defaultVariant->id;
            foreach ($defaultVariant->optionValues as $optionValue) {
                $this->selectedOptions[$optionValue->option->name] = $optionValue->value;
            }
        }
    }

    public function updatedSelectedOptions(): void
    {
        $variant = $this->findMatchingVariant();
        $this->selectedVariantId = $variant?->id;
        $this->quantity = 1;
    }

    public function incrementQuantity(): void
    {
        $variant = $this->getSelectedVariant();
        $max = $this->getMaxQuantity($variant);
        if ($max === null || $this->quantity < $max) {
            $this->quantity++;
        }
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart(): void
    {
        $variant = $this->getSelectedVariant();
        if (! $variant) {
            return;
        }

        $inventory = $variant->inventoryItem;
        if ($inventory && $inventory->policy === InventoryPolicy::Deny && $inventory->quantityAvailable() <= 0) {
            return;
        }

        $store = app()->bound('current_store') ? app('current_store') : null;
        if (! $store) {
            return;
        }

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);

        try {
            $cartService->addLine($cart, $variant->id, $this->quantity);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->quantity = 1;
        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
    }

    public function getSelectedVariant(): ?ProductVariant
    {
        if (! $this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->firstWhere('id', $this->selectedVariantId);
    }

    private function findMatchingVariant(): ?ProductVariant
    {
        if (empty($this->selectedOptions)) {
            return null;
        }

        foreach ($this->product->variants as $variant) {
            $matches = true;
            foreach ($this->selectedOptions as $optionName => $value) {
                $hasMatch = $variant->optionValues->contains(function ($ov) use ($optionName, $value) {
                    return $ov->option->name === $optionName && $ov->value === $value;
                });
                if (! $hasMatch) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return $variant;
            }
        }

        return null;
    }

    private function getMaxQuantity(?ProductVariant $variant): ?int
    {
        if (! $variant) {
            return null;
        }

        $inventory = $variant->inventoryItem;
        if (! $inventory || $inventory->policy === InventoryPolicy::Continue) {
            return null;
        }

        return max(0, $inventory->quantityAvailable());
    }

    public function render(): mixed
    {
        $selectedVariant = $this->getSelectedVariant();
        $store = app()->bound('current_store') ? app('current_store') : null;

        return view('livewire.storefront.products.show', [
            'selectedVariant' => $selectedVariant,
            'currency' => $store?->default_currency ?? 'EUR',
        ])->layout('layouts.storefront', ['title' => $this->product->title]);
    }
}
