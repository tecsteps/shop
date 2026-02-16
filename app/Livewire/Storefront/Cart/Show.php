<?php

namespace App\Livewire\Storefront\Cart;

use App\Services\CartService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    /** @var array<string, string> */
    protected $listeners = [
        'cart-updated' => '$refresh',
    ];

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);
        $cartService->updateLineQuantity($cart, $lineId, $quantity);
    }

    public function removeLine(int $lineId): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);
        $cartService->removeLine($cart, $lineId);
    }

    public function render(): mixed
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);
        $cart->load('lines.variant.product', 'lines.variant.optionValues');

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
        ]);
    }
}
