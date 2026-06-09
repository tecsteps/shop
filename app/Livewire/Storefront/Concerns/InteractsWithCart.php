<?php

namespace App\Livewire\Storefront\Concerns;

use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\Store;
use App\Services\CartService;
use App\Services\DiscountService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

/**
 * Shared cart actions and presentation data for the cart drawer and the
 * full cart page. The applied (pre-checkout) discount code lives in the
 * session under "cart_discount_code" and is copied onto the checkout when
 * it is created.
 */
trait InteractsWithCart
{
    public string $discountCode = '';

    public ?string $discountError = null;

    public ?string $cartError = null;

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->currentCart();

        if ($cart === null) {
            return;
        }

        $this->cartError = null;

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, max(0, $quantity));
        } catch (InsufficientInventoryException) {
            $this->cartError = __('Not enough stock available for the requested quantity.');
        }

        $this->dispatchCartUpdated($cart);
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->currentCart();

        if ($cart === null) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);

        $this->dispatchCartUpdated($cart);
    }

    public function applyDiscount(): void
    {
        $this->discountError = null;

        $cart = $this->currentCart();
        $code = trim($this->discountCode);

        if ($cart === null || $code === '') {
            return;
        }

        try {
            $discount = app(DiscountService::class)->validate($code, $this->currentStore(), $cart);
        } catch (InvalidDiscountException $exception) {
            $this->discountError = $exception->getMessage();

            return;
        }

        Session::put('cart_discount_code', $discount->code);

        $this->discountCode = '';
        $this->dispatchCartUpdated($cart);
    }

    public function removeDiscount(): void
    {
        Session::forget('cart_discount_code');

        $this->discountError = null;

        if (($cart = $this->currentCart()) !== null) {
            $this->dispatchCartUpdated($cart);
        }
    }

    protected function currentStore(): Store
    {
        return app('current_store');
    }

    protected function currentCustomer(): ?Customer
    {
        return auth('customer')->user();
    }

    protected function currentCart(): ?Cart
    {
        return app(CartService::class)->findFor($this->currentStore(), $this->currentCustomer());
    }

    protected function dispatchCartUpdated(Cart $cart): void
    {
        $this->dispatch('cart-updated', cartId: $cart->getKey(), itemCount: $cart->itemCount());
    }

    /**
     * The validated session discount code with its calculated amount, or
     * null when no valid code is applied to the cart.
     *
     * @return array{code: string, amount: int, free_shipping: bool}|null
     */
    protected function appliedDiscount(?Cart $cart): ?array
    {
        $code = Session::get('cart_discount_code');

        if ($cart === null || blank($code)) {
            return null;
        }

        try {
            $discount = app(DiscountService::class)->validate($code, $this->currentStore(), $cart);
        } catch (InvalidDiscountException) {
            return null;
        }

        $lines = $cart->lines()->with('variant.product')->get()->all();
        $result = app(DiscountService::class)->calculate($discount, $cart->subtotalAmount(), $lines);

        return [
            'code' => $discount->code,
            'amount' => $result->amount,
            'free_shipping' => $result->freeShipping,
        ];
    }

    /**
     * Presentation data for the cart's lines.
     *
     * @return list<array{id: int, title: string, variant_label: string, handle: string|null, quantity: int, unit_price_amount: int, line_total_amount: int, image_url: string|null}>
     */
    protected function cartLineData(?Cart $cart): array
    {
        if ($cart === null) {
            return [];
        }

        return $cart->lines()
            ->with(['variant.product.media', 'variant.optionValues'])
            ->get()
            ->map(function (CartLine $line): array {
                $variant = $line->variant;
                $product = $variant?->product;
                $media = $product?->media->first();

                return [
                    'id' => $line->getKey(),
                    'title' => $product?->title ?? __('Unavailable product'),
                    'variant_label' => $variant?->optionValues->pluck('value')->implode(' / ') ?? '',
                    'handle' => $product?->handle,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'line_total_amount' => $line->line_subtotal_amount,
                    'image_url' => $media !== null ? Storage::disk('public')->url($media->storage_key) : null,
                ];
            })
            ->all();
    }
}
