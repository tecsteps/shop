<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Services\CartService;
use Livewire\Component;

class Show extends Component
{
    /** @var array<int, int> */
    public array $quantities = [];

    public string $discountCode = '';

    public function mount(CartService $carts): void
    {
        $this->syncQuantities($carts);
    }

    public function updatedQuantities(int $quantity, string $lineId, CartService $carts): void
    {
        $this->updateLine((int) $lineId, $quantity, $carts);
    }

    public function increaseQuantity(int $lineId, CartService $carts): void
    {
        $cart = $this->cart($carts);
        $line = $cart->lines()->findOrFail($lineId);
        $this->updateLine($lineId, min(9999, (int) $line->quantity + 1), $carts);
    }

    public function decreaseQuantity(int $lineId, CartService $carts): void
    {
        $cart = $this->cart($carts);
        $line = $cart->lines()->findOrFail($lineId);
        $this->updateLine($lineId, max(1, (int) $line->quantity - 1), $carts);
    }

    public function removeItem(int $lineId, CartService $carts): void
    {
        $carts->remove($this->cart($carts), $lineId);
        unset($this->quantities[$lineId]);
        $this->dispatch('cart-updated');
    }

    public function applyDiscount(CartService $carts): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);
        $carts->applyDiscount($this->cart($carts), $this->discountCode);
        $this->dispatch('cart-updated');
    }

    public function removeDiscount(CartService $carts): void
    {
        $carts->removeDiscount($this->cart($carts));
        $this->discountCode = '';
        $this->dispatch('cart-updated');
    }

    public function beginCheckout(CartService $carts): mixed
    {
        if (! $this->currentCart($carts)?->lines()->exists()) {
            $this->addError('cart', 'Your cart is empty.');

            return null;
        }

        return $this->redirectRoute('storefront.checkout');
    }

    public function render(CartService $carts): mixed
    {
        $cart = $this->currentCart($carts);
        $this->fillQuantities($cart);

        return view('storefront.cart', ['cart' => $cart])->layout('storefront.layouts.app');
    }

    private function updateLine(int $lineId, int $quantity, CartService $carts): void
    {
        $carts->updateQuantity($this->cart($carts), $lineId, $quantity);
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    private function cart(CartService $carts): Cart
    {
        return $carts->getOrCreateActiveCart(app('current_store'), auth('customer')->user());
    }

    private function currentCart(CartService $carts): ?Cart
    {
        return $carts->findActiveCart(app('current_store'), auth('customer')->user());
    }

    private function syncQuantities(CartService $carts): void
    {
        $this->fillQuantities($this->currentCart($carts));
    }

    private function fillQuantities(?Cart $cart): void
    {
        $this->quantities = $cart?->lines->mapWithKeys(static fn ($line): array => [$line->id => $line->quantity])->all() ?? [];
        $this->discountCode = $cart?->discount_code ?? '';
    }
}
