<?php

namespace App\Livewire\Storefront\Concerns;

use App\Enums\CartStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait ManagesCartLines
{
    public ?int $cartVersion = null;

    public string $discountCodeInput = '';

    public ?string $cartNotice = null;

    protected function findCart(): ?Cart
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();

        $cartId = session()->get('cart_id');

        if ($cartId !== null) {
            $cart = Cart::query()
                ->where('store_id', $store->getKey())
                ->whereKey($cartId)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart !== null) {
                return $cart;
            }
        }

        if ($customer !== null) {
            return Cart::query()
                ->where('store_id', $store->getKey())
                ->where('customer_id', $customer->getKey())
                ->where('status', CartStatus::Active)
                ->first();
        }

        return null;
    }

    protected function cartLinesData(?Cart $cart): array
    {
        if ($cart === null) {
            return [];
        }

        $catalog = app(StorefrontCatalog::class);
        $lines = $cart->cartLines()->with(['variant.product.productMedia', 'variant.optionValues', 'variant.inventoryItem'])->orderBy('id')->get();

        return $lines->map(fn ($line): array => [
            'id' => $line->getKey(),
            'variant_id' => $line->variant_id,
            'title' => $line->variant->product->title,
            'variant' => $catalog->variantOptionLabels($line->variant),
            'sku' => $line->variant->sku,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price_amount,
            'total' => $line->line_total_amount,
            'image_url' => $catalog->primaryImageUrl($line->variant->product),
            'max_quantity' => $catalog->availableQuantity($line->variant),
            'currency' => $line->variant->currency ?? $cart->currency,
            'is_backorder' => $line->variant->inventoryItem !== null
                && $line->variant->inventoryItem->policy === \App\Enums\InventoryPolicy::Continue
                && ($line->variant->inventoryItem->quantity_on_hand - $line->variant->inventoryItem->quantity_reserved) <= 0,
        ])->all();
    }

    /**
     * @return array{subtotal: int, discount: int, total: int, currency: string, item_count: int}
     */
    protected function cartTotals(?Cart $cart): array
    {
        $store = $this->store();

        if ($cart === null) {
            return ['subtotal' => 0, 'discount' => 0, 'total' => 0, 'currency' => $store->default_currency, 'item_count' => 0];
        }

        $subtotal = (int) $cart->cartLines()->sum('line_subtotal_amount');
        $itemCount = (int) $cart->cartLines()->sum('quantity');
        $discount = $this->sessionDiscountAmount($cart, $subtotal);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'currency' => $cart->currency,
            'item_count' => $itemCount,
        ];
    }

    protected function sessionDiscountCode(): ?string
    {
        $code = session()->get('storefront.discount_code');

        return is_string($code) && $code !== '' ? $code : null;
    }

    protected function sessionDiscountAmount(Cart $cart, int $subtotal): int
    {
        $code = $this->sessionDiscountCode();

        if ($code === null) {
            return 0;
        }

        $discounts = app(DiscountService::class);
        $result = $discounts->validate($code, $this->store(), $cart);

        if (! $result->valid || $result->discount === null) {
            return 0;
        }

        $lines = $cart->cartLines()->with('variant')->orderBy('id')->get()->map(fn ($line): array => [
            'line_id' => $line->getKey(),
            'subtotal' => $line->line_subtotal_amount,
            'product_id' => $line->variant->product_id,
        ])->all();

        return (int) $discounts->calculate($result->discount, $subtotal, $lines)['total'];
    }

    public function incrementLine(int $lineId): void
    {
        $cart = $this->findCart();

        if ($cart === null) {
            return;
        }

        $line = $cart->cartLines()->whereKey($lineId)->first();

        if ($line === null) {
            return;
        }

        $this->updateLine($cart, $lineId, $line->quantity + 1);
    }

    public function decrementLine(int $lineId): void
    {
        $cart = $this->findCart();

        if ($cart === null) {
            return;
        }

        $line = $cart->cartLines()->whereKey($lineId)->first();

        if ($line === null) {
            return;
        }

        $this->updateLine($cart, $lineId, $line->quantity - 1);
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->findCart();

        if ($cart === null) {
            return;
        }

        try {
            app(CartService::class)->removeLine($cart, $lineId, $this->cartVersion);
            $this->cartNotice = 'Item removed';
        } catch (CartVersionMismatchException $exception) {
            $this->cartVersion = $exception->currentVersion;
            $this->cartNotice = 'Your cart was updated elsewhere. Please review and try again.';

            return;
        }

        $this->syncCartVersion();
        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        $cart = $this->findCart();

        if ($cart === null) {
            return;
        }

        $code = trim($this->discountCodeInput);

        if ($code === '') {
            $this->addError('discountCodeInput', 'Please enter a discount code.');

            return;
        }

        $result = app(DiscountService::class)->validate($code, $this->store(), $cart);

        if (! $result->valid) {
            $this->addError('discountCodeInput', $result->errorMessage ?? 'Invalid discount code.');

            return;
        }

        session()->put('storefront.discount_code', $result->discount->code);
        $this->discountCodeInput = '';
        $this->cartNotice = 'Discount applied';
    }

    public function removeDiscount(): void
    {
        session()->forget('storefront.discount_code');
        $this->discountCodeInput = '';
    }

    public function startCheckout(): mixed
    {
        $cart = $this->findCart();

        if ($cart === null || $cart->cartLines()->count() === 0) {
            $this->cartNotice = 'Your cart is empty';

            return null;
        }

        $customer = Auth::guard('customer')->user();
        $checkouts = app(CheckoutService::class);

        $existing = Checkout::query()
            ->where('store_id', $this->store()->getKey())
            ->where('cart_id', $cart->getKey())
            ->whereNotIn('status', [\App\Enums\CheckoutStatus::Completed, \App\Enums\CheckoutStatus::Expired])
            ->latest('id')
            ->first();

        $checkout = $existing ?? $checkouts->createFromCart($cart->refresh(), (string) ($customer?->email ?? ''), $customer);

        $code = $this->sessionDiscountCode();

        if ($code !== null && $checkout->discount_code === null) {
            try {
                $checkouts->applyDiscount($checkout->refresh(), $code, app(DiscountService::class));
            } catch (\Throwable) {
            }
        }

        return $this->redirect(route('storefront.checkout.show', ['checkoutId' => $checkout->getKey()]), navigate: true);
    }

    protected function updateLine(Cart $cart, int $lineId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeLine($lineId);

            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $quantity, $this->cartVersion);
            $this->cartNotice = null;
        } catch (CartVersionMismatchException $exception) {
            $this->cartVersion = $exception->currentVersion;
            $this->cartNotice = 'Your cart was updated elsewhere. Please review and try again.';

            return;
        } catch (InsufficientInventoryException) {
            $this->cartNotice = 'Only limited stock is available for this item.';

            return;
        } catch (ValidationException $exception) {
            $this->cartNotice = collect($exception->errors())->flatten()->first() ?? 'Unable to update quantity.';

            return;
        }

        $this->syncCartVersion();
        $this->dispatch('cart-updated');
    }

    protected function syncCartVersion(): void
    {
        $cart = $this->findCart();

        $this->cartVersion = $cart?->cart_version;
    }
}
