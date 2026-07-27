<?php

namespace App\Livewire\Storefront\Concerns;

use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\Services\CartService;
use App\Services\DiscountService;
use Illuminate\Validation\ValidationException;

/**
 * Shared cart line/discount interactions for the cart drawer and the full
 * cart page. Discount codes are validated immediately and kept in the
 * session until checkout creation consumes them.
 */
trait InteractsWithCart
{
    public string $discountCode = '';

    public ?string $discountError = null;

    /**
     * Increment a line's quantity by one.
     */
    public function incrementLine(int $lineId): void
    {
        $this->changeLineQuantity($lineId, 1);
    }

    /**
     * Decrement a line's quantity by one (removes the line at zero).
     */
    public function decrementLine(int $lineId): void
    {
        $this->changeLineQuantity($lineId, -1);
    }

    /**
     * Remove a line from the session cart.
     */
    public function removeLine(int $lineId): void
    {
        $cart = $this->sessionCart();

        if ($cart === null) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->broadcastCartCount($cart->refresh());
    }

    /**
     * Validate the entered discount code and store it in the session.
     */
    public function applyDiscount(): void
    {
        $this->discountError = null;
        $cart = $this->sessionCart();
        $code = trim($this->discountCode);

        if ($cart === null || $code === '') {
            return;
        }

        $result = app(DiscountService::class)->validate($code, $this->currentStore(), $cart);

        if (! $result->valid) {
            $this->discountError = $result->errorMessage;

            return;
        }

        session(['discount_code' => $result->discount->code]);
        $this->discountCode = '';
    }

    /**
     * Remove the session discount code.
     */
    public function removeDiscount(): void
    {
        session()->forget('discount_code');
        $this->discountError = null;
    }

    /**
     * Change a line quantity by a delta, ignoring stock rejections.
     */
    private function changeLineQuantity(int $lineId, int $delta): void
    {
        $cart = $this->sessionCart();
        $line = $cart?->lines->firstWhere('id', $lineId);

        if ($cart === null || $line === null) {
            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $line->quantity + $delta);
        } catch (InsufficientInventoryException|ValidationException) {
            return;
        }

        $this->broadcastCartCount($cart->refresh());
    }

    /**
     * The active cart bound to the session (not created on demand).
     */
    protected function sessionCart(): ?Cart
    {
        return app(CartService::class)->findForSession($this->currentStore());
    }

    /**
     * The store resolved for the current request.
     */
    protected function currentStore(): Store
    {
        return app('current_store');
    }

    /**
     * Tell the header badge the new item count.
     */
    protected function broadcastCartCount(Cart $cart): void
    {
        $this->dispatch('cart-updated', count: $cart->itemCount());
    }

    /**
     * The validated session discount with its calculated amount, if any.
     *
     * @return array{code: string, label: string, amount: int, free_shipping: bool}|null
     */
    protected function appliedSessionDiscount(Cart $cart): ?array
    {
        $code = session('discount_code');

        if ($code === null) {
            return null;
        }

        $discount = Discount::query()
            ->where('store_id', $this->currentStore()->id)
            ->whereRaw('lower(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($discount === null) {
            session()->forget('discount_code');

            return null;
        }

        $result = app(DiscountService::class)->calculate(
            $discount,
            $cart->subtotal(),
            $this->calculationLines($cart),
        );

        $label = match ($discount->value_type) {
            \App\Enums\DiscountValueType::Percent => "-{$discount->value_amount}%",
            \App\Enums\DiscountValueType::Fixed => '-'.\App\Support\Money::format($result['amount'], $cart->currency),
            \App\Enums\DiscountValueType::FreeShipping => 'Free shipping',
        };

        return [
            'code' => $discount->code,
            'label' => $label,
            'amount' => $result['amount'],
            'free_shipping' => $result['free_shipping'],
        ];
    }

    /**
     * Flat calculation representation of the cart lines for discounts.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function calculationLines(Cart $cart): array
    {
        $cart->loadMissing('lines.variant.product.collections');

        return $cart->lines->values()->map(fn ($line): array => [
            'variant_id' => $line->variant_id,
            'product_id' => $line->variant?->product_id,
            'collection_ids' => $line->variant?->product?->collections->pluck('id')->all() ?? [],
            'line_subtotal_amount' => $line->line_subtotal_amount,
        ])->all();
    }
}
