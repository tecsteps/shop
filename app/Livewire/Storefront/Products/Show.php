<?php

namespace App\Livewire\Storefront\Products;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public string $handle = '';

    /** @var object|null */
    public $product = null;

    /** @var array<string, string> */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public ?int $selectedVariantId = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        if (class_exists(\App\Models\Product::class)) {
            $this->product = \App\Models\Product::query()
                ->where('handle', $handle)
                ->where('status', 'active')
                ->with(['variants', 'options.values', 'media'])
                ->first();

            if (! $this->product) {
                abort(404);
            }

            $this->initializeOptions();
        }
    }

    protected function initializeOptions(): void
    {
        if (! $this->product || ! method_exists($this->product, 'getAttribute')) {
            return;
        }

        $options = $this->product->options ?? collect();

        foreach ($options as $option) {
            $firstValue = $option->values->first();
            if ($firstValue) {
                $this->selectedOptions[$option->name] = $firstValue->value;
            }
        }

        $this->resolveVariant();
    }

    public function updatedSelectedOptions(): void
    {
        $this->resolveVariant();
    }

    protected function resolveVariant(): void
    {
        if (! $this->product) {
            return;
        }

        $variants = $this->product->variants ?? collect();

        foreach ($variants as $variant) {
            $variantOptions = $variant->optionValues ?? collect();
            $match = true;

            foreach ($this->selectedOptions as $optionName => $selectedValue) {
                $found = $variantOptions->first(function ($ov) use ($optionName, $selectedValue) {
                    return $ov->option->name === $optionName && $ov->value === $selectedValue;
                });

                if (! $found) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $this->selectedVariantId = $variant->id;

                return;
            }
        }

        $this->selectedVariantId = $variants->first()?->id;
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

    public function addToCart(): void
    {
        if (! $this->selectedVariantId) {
            return;
        }

        $this->dispatch('cart-updated');
    }

    public function render(): \Illuminate\View\View
    {
        $selectedVariant = null;

        if ($this->product && $this->selectedVariantId) {
            $variants = $this->product->variants ?? collect();
            $selectedVariant = $variants->firstWhere('id', $this->selectedVariantId);
        }

        return view('livewire.storefront.products.show', [
            'selectedVariant' => $selectedVariant,
        ]);
    }
}
