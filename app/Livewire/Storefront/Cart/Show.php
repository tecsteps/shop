<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart as CartModel;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public ?int $cartId = null;

    public function mount(): void
    {
        $this->cartId = session('cart_id');
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->loadCart();

        if ($cart === null) {
            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $quantity);
        } catch (InsufficientInventoryException $e) {
            $this->addError('line_'.$lineId, 'Not enough stock available.');
        }
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->loadCart();

        if ($cart === null) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
    }

    public function render(): View
    {
        $cart = $this->loadCart();

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $cart ? $cart->lines()->with('variant.product')->get() : collect(),
        ]);
    }

    protected function loadCart(): ?CartModel
    {
        if ($this->cartId === null) {
            return null;
        }

        return CartModel::query()->find($this->cartId);
    }
}
