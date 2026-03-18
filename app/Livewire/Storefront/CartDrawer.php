<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $isOpen = false;

    public string $discountCode = '';

    protected $listeners = ['cart-updated' => 'openDrawer'];

    public function openDrawer(): void
    {
        $this->isOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->isOpen = false;
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        if ($quantity <= 0) {
            $cartService->removeLine($cart, $lineId);
        } else {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        }

        $this->dispatch('cart-count-updated');
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->dispatch('cart-count-updated');
    }

    public function getCart(): ?Cart
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->with(['lines.variant.product', 'lines.variant.optionValues.option'])
            ->find($cartId);
    }

    public function render(): View
    {
        return view('livewire.storefront.cart-drawer', [
            'cart' => $this->getCart(),
        ]);
    }
}
