<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartService
{
    /**
     * Create a new cart for a store, optionally linked to a customer.
     */
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    /**
     * Get or create a cart bound to the session for guest users, or the customer.
     */
    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        if ($customer) {
            $cart = Cart::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                return $cart;
            }

            return $this->create($store, $customer);
        }

        $cartId = session('cart_id');

        if ($cartId) {
            $cart = Cart::withoutGlobalScopes()
                ->where('id', $cartId)
                ->where('store_id', $store->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        $cart = $this->create($store);
        session(['cart_id' => $cart->id]);

        return $cart;
    }

    /**
     * Add a line item to the cart.
     *
     * @throws InvalidArgumentException
     * @throws InsufficientInventoryException
     */
    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $quantity, $expectedVersion) {
            $cart->refresh();
            $this->checkVersion($cart, $expectedVersion);

            $variant = ProductVariant::query()
                ->withoutGlobalScopes()
                ->with(['product', 'inventoryItem'])
                ->findOrFail($variantId);

            if ($variant->product->status !== ProductStatus::Active) {
                throw new InvalidArgumentException('Product is not active');
            }

            if ($variant->status !== VariantStatus::Active) {
                throw new InvalidArgumentException('Variant is not active');
            }

            $this->checkInventory($variant, $quantity);

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();

            if ($existingLine) {
                $newQuantity = $existingLine->quantity + $quantity;
                $this->checkInventory($variant, $newQuantity);

                $existingLine->update([
                    'quantity' => $newQuantity,
                    'line_subtotal_amount' => $variant->price_amount * $newQuantity,
                    'line_total_amount' => $variant->price_amount * $newQuantity - $existingLine->line_discount_amount,
                ]);

                $cart->increment('cart_version');

                return $existingLine->refresh();
            }

            $subtotal = $variant->price_amount * $quantity;

            $line = $cart->lines()->create([
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $subtotal,
                'line_discount_amount' => 0,
                'line_total_amount' => $subtotal,
            ]);

            $cart->increment('cart_version');

            return $line;
        });
    }

    /**
     * Update the quantity of a line item.
     */
    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion) {
            $cart->refresh();
            $this->checkVersion($cart, $expectedVersion);

            $line = $cart->lines()->findOrFail($lineId);

            if ($quantity <= 0) {
                $this->removeLine($cart, $lineId, $expectedVersion);

                return $line;
            }

            $variant = ProductVariant::withoutGlobalScopes()
                ->with('inventoryItem')
                ->findOrFail($line->variant_id);

            $this->checkInventory($variant, $quantity);

            $subtotal = $line->unit_price_amount * $quantity;

            $line->update([
                'quantity' => $quantity,
                'line_subtotal_amount' => $subtotal,
                'line_total_amount' => $subtotal - $line->line_discount_amount,
            ]);

            $cart->increment('cart_version');

            return $line->refresh();
        });
    }

    /**
     * Remove a line item from the cart.
     */
    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion) {
            $cart->refresh();
            $this->checkVersion($cart, $expectedVersion);

            $cart->lines()->findOrFail($lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    /**
     * Merge a guest cart into a customer cart on login.
     * Uses the higher quantity for duplicate variants.
     */
    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart) {
            $guestCart->load('lines');
            $customerCart->load('lines');

            foreach ($guestCart->lines as $guestLine) {
                $existingLine = $customerCart->lines->firstWhere('variant_id', $guestLine->variant_id);

                if ($existingLine) {
                    $newQuantity = max($existingLine->quantity, $guestLine->quantity);
                    $existingLine->update([
                        'quantity' => $newQuantity,
                        'line_subtotal_amount' => $existingLine->unit_price_amount * $newQuantity,
                        'line_total_amount' => $existingLine->unit_price_amount * $newQuantity,
                    ]);
                } else {
                    $customerCart->lines()->create([
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
            $customerCart->increment('cart_version');

            return $customerCart->refresh()->load('lines');
        });
    }

    /**
     * Check cart version for optimistic concurrency control.
     *
     * @throws CartVersionMismatchException
     */
    private function checkVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
        }
    }

    /**
     * Check inventory availability for a variant.
     *
     * @throws InsufficientInventoryException
     */
    private function checkInventory(ProductVariant $variant, int $quantity): void
    {
        $inventoryItem = $variant->inventoryItem;

        if (! $inventoryItem) {
            return;
        }

        if ($inventoryItem->policy === InventoryPolicy::Continue) {
            return;
        }

        if ($inventoryItem->availableQuantity() < $quantity) {
            throw new InsufficientInventoryException(
                $variant->id,
                $quantity,
                $inventoryItem->availableQuantity(),
            );
        }
    }
}
