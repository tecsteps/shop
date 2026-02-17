<?php

namespace App\Livewire\Storefront\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public string $handle = '';

    public int $quantity = 1;

    /** @var array<string, string> */
    public array $selectedOptions = [];

    public ?int $selectedVariantId = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $product = $this->getProduct();
        if ($product) {
            $defaultVariant = $product->variants->firstWhere('is_default', true)
                ?? $product->variants->first();

            if ($defaultVariant) {
                $this->selectedVariantId = $defaultVariant->id;
                $this->initializeOptionsFromVariant($product, $defaultVariant);
            }
        }
    }

    public function updatedSelectedOptions(): void
    {
        $product = $this->getProduct();
        if (! $product) {
            return;
        }

        $this->selectedVariantId = $this->findMatchingVariant($product)?->id;
    }

    public function addToCart(): void
    {
        // Placeholder - will be implemented in the Cart phase
        $this->dispatch('cart-updated');
    }

    public function render(): \Illuminate\View\View
    {
        $product = $this->getProduct();

        if (! $product) {
            abort(404);
        }

        $selectedVariant = $product->variants->firstWhere('id', $this->selectedVariantId);
        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
        ])->title("{$product->title} - {$storeName}");
    }

    protected function getProduct(): ?Product
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        return Product::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->where('handle', $this->handle)
            ->where('status', 'active')
            ->with([
                'variants.inventoryItem',
                'variants.optionValues.option',
                'media',
                'options.values',
            ])
            ->first();
    }

    protected function initializeOptionsFromVariant(Product $product, ProductVariant $variant): void
    {
        foreach ($variant->optionValues as $optionValue) {
            $optionName = $optionValue->option->name;
            $this->selectedOptions[$optionName] = $optionValue->value;
        }
    }

    protected function findMatchingVariant(Product $product): ?ProductVariant
    {
        foreach ($product->variants as $variant) {
            $variantOptions = [];
            foreach ($variant->optionValues as $optionValue) {
                $variantOptions[$optionValue->option->name] = $optionValue->value;
            }

            if ($variantOptions == $this->selectedOptions) {
                return $variant;
            }
        }

        return null;
    }
}
