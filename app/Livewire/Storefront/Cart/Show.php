<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class Show extends Component
{
    public ?int $cartId = null;

    public function mount(CartService $carts): void
    {
        $this->cartId = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user())->id;
    }

    public function updateQuantity(int $lineId, int $quantity, CartService $carts): void
    {
        $carts->updateLineQuantity($this->cart(), $lineId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function removeLine(int $lineId, CartService $carts): void
    {
        $carts->removeLine($this->cart(), $lineId);
        session()->flash('storefront_status', 'Item removed');
        $this->dispatch('cart-updated');
    }

    public function checkout(CheckoutService $checkouts): RedirectResponse
    {
        $checkout = $checkouts->create($this->cart());

        return redirect()->route('storefront.checkout.show', $checkout->id);
    }

    public function render(): View
    {
        return view('livewire.storefront.cart.show', ['cart' => $this->cart()])
            ->layout('layouts.storefront', ['title' => 'Cart - '.app('current_store')->name]);
    }

    private function cart(): Cart
    {
        return Cart::query()->with(['lines.variant.product', 'lines.variant.inventoryItem'])->findOrFail($this->cartId);
    }
}
