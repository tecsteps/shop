<?php

namespace App\Livewire\Storefront;

use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public Cart $cart;

    public string $discountCode = '';

    public int $discountAmount = 0;

    public string $message = '';

    public function mount(CartService $carts): void
    {
        $this->cart = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user());
        $this->discountCode = (string) ($this->cart->discount_code ?? '');
        $this->dispatch('cart-count-updated', count: $this->cart->itemCount());
    }

    #[On('open-cart-drawer')]
    public function open(): void
    {
        $this->refreshCart();
        $this->open = true;
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->cart = app(CartService::class)->getOrCreateForSession(app('current_store'), auth('customer')->user());
        $this->discountCode = (string) ($this->cart->discount_code ?? '');
        $this->discountAmount = $this->calculateDiscountAmount();
        $this->open = true;
        $this->dispatch('cart-count-updated', count: $this->cart->itemCount());
    }

    public function increase(int $lineId): void
    {
        $line = $this->cart->lines->firstWhere('id', $lineId);

        if ($line === null) {
            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($this->cart, $lineId, $line->quantity + 1);
            $this->refreshCart();
        } catch (InsufficientInventoryException $exception) {
            $this->addError('cart', $exception->getMessage());
        }
    }

    public function decrease(int $lineId): void
    {
        $line = $this->cart->lines->firstWhere('id', $lineId);

        if ($line === null || $line->quantity < 2) {
            return;
        }

        app(CartService::class)->updateLineQuantity($this->cart, $lineId, $line->quantity - 1);
        $this->refreshCart();
    }

    public function remove(int $lineId): void
    {
        if (! $this->cart->lines->contains('id', $lineId)) {
            return;
        }

        app(CartService::class)->removeLine($this->cart, $lineId);
        $this->message = 'Item removed from your cart.';
        $this->refreshCart();
    }

    public function applyDiscount(DiscountService $discounts): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);

        try {
            $discount = $discounts->validate(trim($this->discountCode), app('current_store'), $this->cart);
            $this->cart->update(['discount_code' => $discount->code]);
            $this->message = 'Discount applied.';
            $this->resetValidation('discountCode');
            $this->refreshCart();
        } catch (InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function removeDiscount(): void
    {
        $this->cart->update(['discount_code' => null]);
        $this->discountCode = '';
        $this->message = 'Discount removed.';
        $this->resetValidation('discountCode');
        $this->refreshCart();
    }

    public function checkout(CheckoutService $checkouts): void
    {
        if ($this->cart->lines->isEmpty()) {
            $this->addError('cart', 'Your cart is empty.');

            return;
        }

        $checkout = $checkouts->create($this->cart, auth('customer')->user()?->email ?? 'guest@example.com', auth('customer')->user());
        $this->redirect(route('checkout.show', $checkout), navigate: true);
    }

    public function formatMoney(int|float $amount): string
    {
        return number_format((float) $amount / 100, 2, '.', ',').' '.($this->cart->currency ?: 'EUR');
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart-drawer');
    }

    private function calculateDiscountAmount(): int
    {
        if ($this->cart->discount_code === null || $this->cart->lines->isEmpty()) {
            return 0;
        }

        try {
            $discount = app(DiscountService::class)->validate($this->cart->discount_code, app('current_store'), $this->cart);
        } catch (InvalidDiscountException) {
            return 0;
        }

        return app(DiscountService::class)->calculate($discount, (int) $this->cart->lines->sum('line_subtotal_amount'), $this->cart->lines->map(fn ($line): array => [
            'line_id' => $line->id,
            'amount' => $line->line_subtotal_amount,
            'product_id' => $line->variant->product_id,
            'collection_ids' => $line->variant->product->collections->modelKeys(),
        ])->all())->amount;
    }
}
