<?php

namespace App\Livewire\Storefront;

use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InvalidCartMutationException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public string $discountCode = '';

    protected $listeners = [
        'open-cart' => 'open',
    ];

    public function open(): void
    {
        $this->open = true;
        $this->syncDiscountCode();
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
            $this->syncDiscountCode();
        } catch (CartVersionConflictException|InvalidCartMutationException $exception) {
            $this->addError('cart', $exception->getMessage());
        }
    }

    public function applyDiscount(): void
    {
        $this->discountCode = trim($this->discountCode);

        $this->validate([
            'discountCode' => ['required', 'string', 'max:50'],
        ]);

        $cart = $this->cart();

        if (! $cart) {
            return;
        }

        try {
            $cart = app(CartService::class)->applyDiscount($cart, $this->discountCode);
            $this->discountCode = $cart->discount_code ?? '';
            $this->resetErrorBag('cart');
            $this->resetErrorBag('discountCode');
            $this->dispatch('cart-updated');
        } catch (InvalidCartMutationException|InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function removeDiscount(): void
    {
        $cart = $this->cart();

        if (! $cart) {
            return;
        }

        try {
            app(CartService::class)->removeDiscount($cart);
            $this->discountCode = '';
            $this->resetErrorBag('cart');
            $this->resetErrorBag('discountCode');
            $this->dispatch('cart-updated');
        } catch (InvalidCartMutationException $exception) {
            $this->addError('discountCode', $exception->getMessage());
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
        if (! app()->bound('current_store')) {
            return null;
        }

        $cart = app(CartService::class)->findActiveForSession(app('current_store'));

        return $cart instanceof Cart ? app(CartService::class)->loadForDisplay($cart) : null;
    }

    private function syncDiscountCode(): void
    {
        $this->discountCode = $this->cart()?->discount_code ?? '';
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
            $this->syncDiscountCode();
        } catch (CartVersionConflictException|InvalidCartMutationException $exception) {
            $this->addError('cart', $exception->getMessage());
        }
    }
}
