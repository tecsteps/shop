<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public array $selectedOptions = [];

    public int $quantity = 1;

    public int $selectedImageIndex = 0;

    public function mount(string $handle): void
    {
        $this->product = Product::query()
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->with(['variants.optionValues.option', 'variants.inventoryItem', 'options.values', 'media'])
            ->firstOrFail();

        $defaultVariant = $this->product->variants->firstWhere('is_default', true)
            ?? $this->product->variants->first();

        if ($defaultVariant) {
            foreach ($defaultVariant->optionValues as $optionValue) {
                $this->selectedOptions[$optionValue->option->name] = $optionValue->value;
            }
        }
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        if (empty($this->selectedOptions)) {
            return $this->product->variants->firstWhere('is_default', true);
        }

        return $this->product->variants->first(function (ProductVariant $variant) {
            $variantOptions = $variant->optionValues->mapWithKeys(
                fn ($ov) => [$ov->option->name => $ov->value]
            )->all();

            return $variantOptions == $this->selectedOptions;
        });
    }

    #[Computed]
    public function stockInfo(): array
    {
        $variant = $this->selectedVariant;

        if (! $variant || ! $variant->inventoryItem) {
            return ['status' => 'unavailable', 'message' => 'Unavailable', 'canAddToCart' => false];
        }

        $inventory = $variant->inventoryItem;
        $available = $inventory->quantity_available;

        if ($available > 10) {
            return ['status' => 'in_stock', 'message' => 'In stock', 'canAddToCart' => true];
        }

        if ($available > 0) {
            return ['status' => 'low_stock', 'message' => "Only {$available} left in stock", 'canAddToCart' => true];
        }

        if ($inventory->policy === InventoryPolicy::Continue) {
            return ['status' => 'backorder', 'message' => 'Available on backorder', 'canAddToCart' => true];
        }

        return ['status' => 'sold_out', 'message' => 'Out of stock', 'canAddToCart' => false];
    }

    public function updatedSelectedOptions(): void
    {
        $this->quantity = 1;
    }

    public function render(): View
    {
        return view('livewire.storefront.products.show')
            ->layout('storefront.layouts.app', ['title' => $this->product->title]);
    }
}
