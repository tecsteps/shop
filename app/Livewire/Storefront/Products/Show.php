<?php

namespace App\Livewire\Storefront\Products;

use App\Exceptions\InsufficientInventoryException;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    public Product $product;

    public int $selectedVariantId;

    public ?int $selectedMediaId = null;

    /** @var array<int, int> */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public string $message = '';

    public function mount(string $handle): void
    {
        $this->product = Product::query()->with(['variants.inventory', 'variants.optionValues', 'media', 'options.values'])->where('handle', $handle)->firstOrFail();
        abort_unless($this->product->status->value === 'active', 404);
        $this->selectedVariantId = $this->product->defaultVariant()?->getKey() ?? 0;
        $this->selectedMediaId = $this->product->media->first()?->getKey();
        $default = $this->product->defaultVariant();
        $this->selectedOptions = $default?->optionValues->mapWithKeys(fn ($value): array => [$value->product_option_id => $value->getKey()])->all() ?? [];
    }

    public function addToCart(CartService $carts): void
    {
        $cart = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user());
        try {
            $carts->addLine($cart, $this->selectedVariantId, $this->quantity);
        } catch (InsufficientInventoryException $exception) {
            $this->addError('quantity', $exception->getMessage());

            return;
        }
        $this->message = 'Added to cart';
        $this->dispatch('cart-updated');
    }

    public function selectVariant(int $variantId): void
    {
        abort_unless($this->product->variants->contains('id', $variantId), 404);

        $this->selectedVariantId = $variantId;
    }

    public function selectMedia(int $mediaId): void
    {
        abort_unless($this->product->media->contains('id', $mediaId), 404);

        $this->selectedMediaId = $mediaId;
    }

    public function selectOption(int $optionId, int $valueId): void
    {
        abort_unless($this->product->options->firstWhere('id', $optionId)?->values->contains('id', $valueId), 404);
        $this->selectedOptions[$optionId] = $valueId;
        $selectedValueIds = array_values($this->selectedOptions);
        $variant = $this->product->variants->first(function ($candidate) use ($selectedValueIds): bool {
            $candidateValueIds = $candidate->optionValues->modelKeys();

            return count($selectedValueIds) === count($candidateValueIds)
                && count(array_intersect($selectedValueIds, $candidateValueIds)) === count($selectedValueIds);
        });

        if ($variant !== null) {
            $this->selectedVariantId = $variant->getKey();
        }
    }

    public function render(): mixed
    {
        $this->product->loadMissing(['variants.inventory', 'variants.optionValues', 'media', 'options.values']);

        return view('livewire.storefront.products.show')->layout('layouts.storefront');
    }
}
