<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use App\Services\DiscountCalculator;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $isOpen = false;

    /** @var array<int, int> */
    public array $quantities = [];

    public string $discountCode = '';

    #[On('open-cart-drawer')]
    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->syncQuantities(app(CartService::class));
    }

    public function updatedQuantities(int $quantity, string $lineId, CartService $carts): void
    {
        $carts->updateQuantity($this->cart($carts), (int) $lineId, $quantity);
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function increaseQuantity(int $lineId, CartService $carts): void
    {
        $line = $this->cart($carts)->lines()->findOrFail($lineId);
        $carts->updateQuantity($this->cart($carts), $lineId, min(9999, (int) $line->quantity + 1));
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function decreaseQuantity(int $lineId, CartService $carts): void
    {
        $line = $this->cart($carts)->lines()->findOrFail($lineId);
        $carts->updateQuantity($this->cart($carts), $lineId, max(1, (int) $line->quantity - 1));
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function removeItem(int $lineId, CartService $carts): void
    {
        $carts->remove($this->cart($carts), $lineId);
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function applyDiscount(CartService $carts, DiscountCalculator $discounts): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);
        $carts->applyDiscount($this->cart($carts), $this->discountCode, $discounts);
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function removeDiscount(CartService $carts): void
    {
        $carts->removeDiscount($this->cart($carts));
        $this->discountCode = '';
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function render(CartService $carts): mixed
    {
        $cart = $carts->findActiveCart(app('current_store'), auth('customer')->user());
        $this->fillQuantities($cart);

        return view('storefront.components.cart-drawer', ['cart' => $cart]);
    }

    private function cart(CartService $carts): Cart
    {
        return $carts->getOrCreateActiveCart(app('current_store'), auth('customer')->user());
    }

    private function syncQuantities(CartService $carts): void
    {
        $this->fillQuantities($this->currentCart($carts));
    }

    private function currentCart(CartService $carts): ?Cart
    {
        return $carts->findActiveCart(app('current_store'), auth('customer')->user());
    }

    private function fillQuantities(?Cart $cart): void
    {
        $this->quantities = $cart?->lines->mapWithKeys(static fn ($line): array => [$line->id => $line->quantity])->all() ?? [];
        $this->discountCode = $cart?->discount_code ?? '';
    }
}
