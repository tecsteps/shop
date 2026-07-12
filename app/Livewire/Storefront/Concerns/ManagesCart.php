<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait ManagesCart
{
    public string $discountCode = '';

    public ?string $discountError = null;

    public int $discountAmount = 0;

    public bool $freeShippingDiscount = false;

    #[Computed]
    public function cart(): ?Cart
    {
        $store = $this->currentStore();
        $cart = app(CartService::class)->resolveForSession($store, Auth::guard('customer')->user());

        return $cart?->load([
            'lines.variant.product.media',
            'lines.variant.optionValues.option',
            'lines.variant.inventoryItem',
        ]);
    }

    #[Computed]
    public function cartSubtotal(): int
    {
        return (int) ($this->cart?->lines->sum('line_subtotal_amount') ?? 0);
    }

    #[Computed]
    public function cartTotal(): int
    {
        return max(0, $this->cartSubtotal - $this->discountAmount);
    }

    #[Computed]
    public function cartItemCount(): int
    {
        return (int) ($this->cart?->lines->sum('quantity') ?? 0);
    }

    public function incrementLine(int $lineId): void
    {
        $line = $this->cartLine($lineId);
        $this->setLineQuantity($lineId, (int) $line->quantity + 1);
    }

    public function decrementLine(int $lineId): void
    {
        $line = $this->cartLine($lineId);
        $quantity = (int) $line->quantity - 1;

        if ($quantity < 1) {
            $this->removeLine($lineId);

            return;
        }

        $this->setLineQuantity($lineId, $quantity);
    }

    public function setLineQuantity(int $lineId, int|string $quantity): void
    {
        $line = $this->cartLine($lineId);
        $quantity = (int) $quantity;

        if ($quantity < 1) {
            $this->removeLine($lineId);

            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($this->cart, $line->getKey(), $quantity);
            $this->cartChanged('Cart updated');
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: str_contains(strtolower($exception->getMessage()), 'stock')
                ? 'Only the available quantity can be added.'
                : 'The cart could not be updated.');
        }
    }

    public function removeLine(int $lineId): void
    {
        $line = $this->cartLine($lineId);
        app(CartService::class)->removeLine($this->cart, $line->getKey());
        $this->cartChanged('Item removed');
    }

    public function applyDiscount(): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:100']]);
        abort_unless($this->cart !== null, 404);

        try {
            $discountService = app(DiscountService::class);
            $discount = $discountService->validate($this->discountCode, $this->currentStore(), $this->cart);
            $result = $discountService->calculate($discount, $this->cartSubtotal, $this->cart->lines->all());

            $this->discountCode = strtoupper((string) $discount->code);
            $this->discountAmount = $this->discountAmountFrom($result, $discount);
            $this->freeShippingDiscount = (bool) data_get($result, 'freeShipping', data_get($result, 'free_shipping', $this->enumValue($discount->value_type) === 'free_shipping'));
            $this->discountError = null;
            session([
                'cart_discount_code' => $this->discountCode,
                'cart_discount_amount' => $this->discountAmount,
                'cart_free_shipping' => $this->freeShippingDiscount,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Discount applied');
        } catch (\Throwable $exception) {
            $message = strtolower($exception->getMessage());
            $this->discountError = str_contains($message, 'expired') ? 'This code has expired.'
                : (str_contains($message, 'limit') || str_contains($message, 'usage') ? 'This code has reached its usage limit.' : 'Invalid discount code');
            $this->discountAmount = 0;
            $this->freeShippingDiscount = false;
        }
    }

    public function removeDiscount(): void
    {
        $this->reset('discountCode', 'discountError', 'discountAmount', 'freeShippingDiscount');
        session()->forget(['cart_discount_code', 'cart_discount_amount', 'cart_free_shipping']);
        $this->dispatch('toast', type: 'info', message: 'Discount removed');
    }

    public function startCheckout(): mixed
    {
        $cart = $this->cart;
        if (! $cart || $cart->lines->isEmpty()) {
            $this->dispatch('toast', type: 'info', message: 'Your cart is empty');

            return null;
        }

        $service = app(CheckoutService::class);
        if (method_exists($service, 'create')) {
            $checkout = $service->create($cart);
        } elseif (method_exists($service, 'createFromCart')) {
            $checkout = $service->createFromCart($cart, Auth::guard('customer')->user());
        } else {
            $checkout = Checkout::query()->firstOrCreate(
                ['cart_id' => $cart->getKey(), 'status' => 'started'],
                [
                    'store_id' => $this->currentStore()->getKey(),
                    'customer_id' => Auth::guard('customer')->id(),
                    'email' => Auth::guard('customer')->user()?->email,
                    'discount_code' => session('cart_discount_code'),
                    'expires_at' => now()->addDay(),
                ],
            );
        }

        if (session('cart_discount_code') && ! $checkout->discount_code) {
            $checkout->forceFill(['discount_code' => session('cart_discount_code')])->save();
        }

        session()->put('checkout_access.'.$checkout->getKey(), true);

        $this->dispatch('close-cart-drawer');

        return $this->redirect(url('/checkout/'.$checkout->getRouteKey()), navigate: true);
    }

    protected function initializeCartDiscount(): void
    {
        $this->discountCode = (string) session('cart_discount_code', '');
        $this->discountAmount = (int) session('cart_discount_amount', 0);
        $this->freeShippingDiscount = (bool) session('cart_free_shipping', false);
    }

    private function cartLine(int $lineId): mixed
    {
        abort_unless($this->cart, 404);
        $line = $this->cart->lines->firstWhere('id', $lineId);
        abort_unless($line, 404);

        return $line;
    }

    private function cartChanged(string $message): void
    {
        unset($this->cart, $this->cartSubtotal, $this->cartTotal, $this->cartItemCount);
        $this->discountAmount = 0;
        session()->forget('cart_discount_amount');

        $cart = $this->cart;
        $this->dispatch('cart-updated', cartId: $cart?->getKey(), itemCount: $this->cartItemCount);
        $this->dispatch('toast', type: 'success', message: $message);
    }

    private function discountAmountFrom(mixed $result, Discount $discount): int
    {
        $amount = data_get($result, 'amount', data_get($result, 'discountAmount', data_get($result, 'discount_amount')));
        if ($amount !== null) {
            return min($this->cartSubtotal, max(0, (int) $amount));
        }

        return match ($this->enumValue($discount->value_type)) {
            'percent' => (int) floor($this->cartSubtotal * ((int) $discount->value_amount / 100)),
            'fixed' => min($this->cartSubtotal, (int) $discount->value_amount),
            default => 0,
        };
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
