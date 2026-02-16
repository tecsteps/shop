<?php

namespace App\Livewire\Storefront;

use App\Services\CartService;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    /** @var array<string, string> */
    protected $listeners = [
        'cart-updated' => 'openDrawer',
        'open-cart-drawer' => 'openDrawer',
        'close-cart-drawer' => 'closeDrawer',
    ];

    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function closeDrawer(): void
    {
        $this->open = false;
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);

        try {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        } catch (\App\Exceptions\InsufficientInventoryException $e) {
            session()->flash('error', 'Not enough stock available. Only '.$e->available.' items left.');
        }
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
        $cart = null;
        if (app()->bound('current_store')) {
            $store = app('current_store');
            $cartService = app(CartService::class);
            $cart = $cartService->getOrCreateForSession($store);
            $cart->load('lines.variant.product');
        }

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
        ]);
    }
}
