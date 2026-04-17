<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;

class CartService
{
    /**
     * Create a new cart for the given store and optional customer.
     */
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        /** @var Cart $cart */
        $cart = Cart::query()->withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => 'EUR',
            'status' => CartStatus::Active,
            'cart_version' => 1,
        ]);

        return $cart;
    }

    /**
     * Add a line item to the cart.
     *
     * @throws InsufficientInventoryException
     */
    public function addLine(Cart $cart, int $variantId, int $qty, ?int $expectedVersion = null): CartLine
    {
        if ($expectedVersion !== null) {
            $this->checkVersion($cart, $expectedVersion);
        }

        $variant = ProductVariant::query()->with('product', 'inventoryItem')->findOrFail($variantId);

        if ($variant->product->status !== ProductStatus::Active) {
            throw new \InvalidArgumentException('Product is not active.');
        }

        $inventoryItem = $variant->inventoryItem;
        if ($inventoryItem && $inventoryItem->policy === InventoryPolicy::Deny) {
            if ($inventoryItem->quantityAvailable() < $qty) {
                throw new InsufficientInventoryException(
                    $variant->id,
                    $qty,
                    $inventoryItem->quantityAvailable()
                );
            }
        }

        /** @var CartLine|null $existingLine */
        $existingLine = CartLine::query()
            ->where('cart_id', $cart->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($existingLine) {
            $newQty = $existingLine->quantity + $qty;

            if ($inventoryItem && $inventoryItem->policy === InventoryPolicy::Deny) {
                if ($inventoryItem->quantityAvailable() < $newQty) {
                    throw new InsufficientInventoryException(
                        $variant->id,
                        $newQty,
                        $inventoryItem->quantityAvailable()
                    );
                }
            }

            $existingLine->quantity = $newQty;
            $existingLine->recalculateAmounts();
            $existingLine->save();
            $cart->incrementVersion();

            return $existingLine;
        }

        /** @var CartLine $line */
        $line = CartLine::query()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variantId,
            'quantity' => $qty,
            'unit_price_amount' => $variant->price_amount,
            'line_subtotal_amount' => $variant->price_amount * $qty,
            'line_discount_amount' => 0,
            'line_total_amount' => $variant->price_amount * $qty,
        ]);

        $cart->incrementVersion();

        return $line;
    }

    /**
     * Update the quantity of a cart line.
     */
    public function updateLineQuantity(Cart $cart, int $lineId, int $qty, ?int $expectedVersion = null): CartLine
    {
        if ($expectedVersion !== null) {
            $this->checkVersion($cart, $expectedVersion);
        }

        /** @var CartLine $line */
        $line = CartLine::query()->where('cart_id', $cart->id)->findOrFail($lineId);

        if ($qty <= 0) {
            $line->delete();
            $cart->incrementVersion();

            return $line;
        }

        $line->quantity = $qty;
        $line->recalculateAmounts();
        $line->save();
        $cart->incrementVersion();

        return $line;
    }

    /**
     * Remove a specific line item from the cart.
     */
    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        if ($expectedVersion !== null) {
            $this->checkVersion($cart, $expectedVersion);
        }

        CartLine::query()->where('cart_id', $cart->id)->where('id', $lineId)->delete();
        $cart->incrementVersion();
    }

    /**
     * Get or create a cart for the current session.
     */
    public function getOrCreateForSession(Store $store, ?Customer $customer = null, ?string $sessionId = null): Cart
    {
        if ($customer) {
            /** @var Cart|null $cart */
            $cart = Cart::query()->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        if ($sessionId) {
            /** @var Cart|null $cart */
            $cart = Cart::query()->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('session_id', $sessionId)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        $newCart = $this->create($store, $customer);
        if ($sessionId) {
            $newCart->update(['session_id' => $sessionId]);
        }

        return $newCart;
    }

    /**
     * Merge a guest cart into a customer cart on login.
     */
    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CartLine> $guestLines */
        $guestLines = CartLine::query()->where('cart_id', $guestCart->id)->get();

        foreach ($guestLines as $guestLine) {
            /** @var CartLine|null $existingLine */
            $existingLine = CartLine::query()
                ->where('cart_id', $customerCart->id)
                ->where('variant_id', $guestLine->variant_id)
                ->first();

            if ($existingLine) {
                $existingLine->quantity += $guestLine->quantity;
                $existingLine->recalculateAmounts();
                $existingLine->save();
            } else {
                CartLine::query()->create([
                    'cart_id' => $customerCart->id,
                    'variant_id' => $guestLine->variant_id,
                    'quantity' => $guestLine->quantity,
                    'unit_price_amount' => $guestLine->unit_price_amount,
                    'line_subtotal_amount' => $guestLine->line_subtotal_amount,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $guestLine->line_subtotal_amount,
                ]);
            }
        }

        $guestCart->update(['status' => CartStatus::Abandoned]);
        $customerCart->incrementVersion();

        return $customerCart->refresh();
    }

    /**
     * @throws CartVersionMismatchException
     */
    public function checkVersion(Cart $cart, int $expectedVersion): void
    {
        $cart->refresh();
        if ($cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
        }
    }
}
