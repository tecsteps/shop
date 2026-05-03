<?php

namespace App\Livewire\Storefront;

use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InvalidCartMutationException;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    protected $listeners = [
        'cart-updated' => 'open',
    ];

    public function open(): void
    {
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function incrementLine(int $lineId): void
    {
        $cart = $this->cart();

        if (! $cart) {
            return;
        }

        $line = $cart->lines->firstWhere('id', $lineId);

        if (! $line) {
            return;
        }

        $this->setLineQuantity($lineId, $line->quantity + 1);
    }

    public function decrementLine(int $lineId): void
    {
        $cart = $this->cart();

        if (! $cart) {
            return;
        }

        $line = $cart->lines->firstWhere('id', $lineId);

        if (! $line) {
            return;
        }

        $this->setLineQuantity($lineId, $line->quantity - 1);
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->cart();

        if (! $cart) {
            return;
        }

        try {
            app(CartService::class)->removeLine($cart, $lineId, $cart->cart_version);
            $this->resetErrorBag('cart');
        } catch (CartVersionConflictException|InvalidCartMutationException $exception) {
            $this->addError('cart', $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.cart-drawer', [
            'cart' => $this->cart(),
        ]);
    }

    private function cart(): ?Cart
    {
        $cartId = session(CartService::SESSION_KEY);

        if (! $cartId || ! app()->bound('current_store')) {
            return null;
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->whereKey($cartId)
            ->first();

        return $cart instanceof Cart ? app(CartService::class)->loadForDisplay($cart) : null;
    }

    private function setLineQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->cart();

        if (! $cart) {
            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $quantity, $cart->cart_version);
            $this->resetErrorBag('cart');
        } catch (CartVersionConflictException|InvalidCartMutationException $exception) {
            $this->addError('cart', $exception->getMessage());
        }
    }
}
