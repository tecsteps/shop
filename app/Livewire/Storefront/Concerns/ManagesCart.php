<?php

namespace App\Livewire\Storefront\Concerns;

use App\Enums\CartStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\DiscountService;

trait ManagesCart
{
    /** @var array<int, int> */
    public array $quantities = [];

    public string $discountCode = '';

    public ?string $discountError = null;

    protected function currentCart(): ?Cart
    {
        $cartId = session('cart_id');

        if (! $cartId) {
            return null;
        }

        $cart = Cart::query()
            ->with(['lines.variant.product.media', 'lines.variant.optionValues'])
            ->where('status', CartStatus::Active)
            ->find($cartId);

        if ($cart) {
            $this->discountCode = session('cart_discount_code', $this->discountCode ?: '');
            foreach ($cart->lines as $line) {
                $this->quantities[$line->id] = $line->quantity;
            }
        }

        return $cart;
    }

    public function updatedQuantities(int $value, string $key): void
    {
        $cart = $this->currentCart();

        if (! $cart) {
            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($cart, (int) $key, max(0, $value));
        } catch (InsufficientInventoryException) {
            $this->addError('quantity', 'Not enough stock available.');
        }

        $this->dispatchCartUpdated();
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->currentCart();

        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        unset($this->quantities[$lineId]);

        $this->dispatchCartUpdated();
    }

    public function applyDiscount(): void
    {
        $this->discountError = null;
        $cart = $this->currentCart();

        if (! $cart || $this->discountCode === '') {
            return;
        }

        try {
            app(DiscountService::class)->validate($this->discountCode, app('current_store'), $cart);
            session(['cart_discount_code' => $this->discountCode]);
        } catch (InvalidDiscountException $exception) {
            $this->discountError = str($exception->reason)->replace('_', ' ')->ucfirst()->value();
        }
    }

    public function removeDiscount(): void
    {
        session()->forget('cart_discount_code');
        $this->discountCode = '';
        $this->discountError = null;
    }

    protected function discountPreview(?Cart $cart): int
    {
        if (! $cart || ! session('cart_discount_code')) {
            return 0;
        }

        try {
            $discount = app(DiscountService::class)->validate(session('cart_discount_code'), app('current_store'), $cart);
            $subtotal = $cart->lines->sum('line_subtotal_amount');

            return app(DiscountService::class)->calculate($discount, $subtotal, $cart->lines)->amount;
        } catch (InvalidDiscountException) {
            return 0;
        }
    }

    protected function dispatchCartUpdated(): void
    {
        $cart = $this->currentCart();

        $this->dispatch('cart-updated', cartId: $cart?->id, itemCount: (int) ($cart?->lines->sum('quantity') ?? 0));
    }
}
