<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\VariantStatus;
use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Product;
use App\Services\CartService;
use App\Support\CartSession;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use EnsuresStore;

    public string $handle = '';

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->ensureCurrentStore();
        $this->handle = $handle;
    }

    public function incrementQuantity(): void
    {
        $this->quantity = min(99, $this->quantity + 1);
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;
    }

    public function addToCart(): void
    {
        if ($this->selectedVariantId === null) {
            $this->addError('cart', 'Please select a variant.');

            return;
        }

        $store = $this->ensureCurrentStore();
        $cart = CartSession::getOrCreate($store);

        try {
            app(CartService::class)->addLine($cart, $this->selectedVariantId, $this->quantity);
        } catch (RuntimeException $exception) {
            $this->addError('cart', $exception->getMessage());

            return;
        }

        $this->dispatch('cart-updated');
        session()->flash('cart-success', 'Added to cart');
    }

    public function render(): View
    {
        $product = Product::query()
            ->where('handle', $this->handle)
            ->with(['variants', 'media'])
            ->firstOrFail();

        $activeVariants = $product->variants->filter(
            fn ($variant): bool => $variant->status === VariantStatus::Active
        )->values();

        if ($this->selectedVariantId === null && $activeVariants->isNotEmpty()) {
            $this->selectedVariantId = (int) $activeVariants->first()->id;
        }

        $selectedVariant = $activeVariants->firstWhere('id', $this->selectedVariantId);

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'activeVariants' => $activeVariants,
            'selectedVariant' => $selectedVariant,
        ]);
    }
}
