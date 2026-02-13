<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    /**
     * @var array<string, string>
     */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->with([
                'variants.inventoryItem',
                'options.values',
                'media',
            ])
            ->firstOrFail();

        $defaultVariant = $this->product->variants->firstWhere('is_default', true)
            ?? $this->product->variants->first();

        if ($defaultVariant) {
            $this->selectedVariantId = $defaultVariant->id;
        }
    }

    public function addToCart(): void
    {
        // Stub - cart functionality will be implemented in Phase 4
        $this->dispatch('cart-updated');
    }

    public function incrementQuantity(): void
    {
        $this->quantity++;
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function render(): View
    {
        $selectedVariant = $this->selectedVariantId
            ? $this->product->variants->find($this->selectedVariantId)
            : $this->product->variants->first();

        $primaryCollection = $this->product->collections->first();

        return view('livewire.storefront.products.show', [
            'selectedVariant' => $selectedVariant,
            'primaryCollection' => $primaryCollection,
        ])->layout('storefront.layouts.app', [
            'title' => $this->product->title,
        ]);
    }
}
