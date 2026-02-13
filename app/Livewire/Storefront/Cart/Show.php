<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\DiscountService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public ?Cart $cart = null;

    public string $discountCode = '';

    public string $discountError = '';

    public string $discountSuccess = '';

    public function mount(): void
    {
        $this->loadCart();
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->loadCart();
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        if (! $this->cart) {
            return;
        }

        $cartService = app(CartService::class);

        if ($quantity < 1) {
            $cartService->removeLine($this->cart, $lineId);
        } else {
            $cartService->updateLineQuantity($this->cart, $lineId, $quantity);
        }

        $this->loadCart();
        $this->dispatch('cart-updated');
    }

    public function removeLine(int $lineId): void
    {
        if (! $this->cart) {
            return;
        }

        $cartService = app(CartService::class);
        $cartService->removeLine($this->cart, $lineId);

        $this->loadCart();
        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        $this->discountError = '';
        $this->discountSuccess = '';

        if (trim($this->discountCode) === '') {
            $this->discountError = 'Please enter a discount code.';

            return;
        }

        if (! $this->cart) {
            return;
        }

        $discountService = app(DiscountService::class);

        try {
            $discount = $discountService->validate(
                $this->discountCode,
                app('current_store'),
                $this->cart->load('lines'),
            );

            session()->put('discount_code', $this->discountCode);
            $this->discountSuccess = "Discount \"{$discount->code}\" applied successfully.";
        } catch (InvalidDiscountException $e) {
            $this->discountError = $e->getMessage();
        }
    }

    public function removeDiscount(): void
    {
        $this->discountCode = '';
        $this->discountError = '';
        $this->discountSuccess = '';
        session()->forget('discount_code');
    }

    public function getSubtotalProperty(): int
    {
        if (! $this->cart || ! $this->cart->lines) {
            return 0;
        }

        return $this->cart->lines->sum('line_subtotal_amount');
    }

    public function getCartItemCountProperty(): int
    {
        if (! $this->cart || ! $this->cart->lines) {
            return 0;
        }

        return $this->cart->lines->sum('quantity');
    }

    public function render(): View
    {
        return view('livewire.storefront.cart.show');
    }

    private function loadCart(): void
    {
        $cartService = app(CartService::class);
        $this->cart = $cartService->getOrCreateForSession(app('current_store'));
        $this->cart->load('lines.variant.product.media', 'lines.variant.inventoryItem');

        $savedCode = session('discount_code');
        if ($savedCode && $this->discountCode === '') {
            $this->discountCode = $savedCode;
        }
    }
}
