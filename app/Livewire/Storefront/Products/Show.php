<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public Product $product;

    public ?int $selectedVariantId = null;

    public function mount(string $handle): void
    {
        $product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->with(['variants', 'options.values', 'media'])
            ->first();

        if (! $product) {
            abort(404);
        }

        $this->product = $product;

        $defaultVariant = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        $this->selectedVariantId = $defaultVariant?->id;
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;
    }

    public function getSelectedVariantProperty(): ?ProductVariant
    {
        if (! $this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->find($this->selectedVariantId);
    }

    public function render(): View
    {
        return view('livewire.storefront.products.show');
    }
}
