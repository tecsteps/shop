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
     * Create a new cart for the given store and optional customer.
     */
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    /**
     * Add a line to the cart, or increment quantity if the variant already exists.
     *
     * @throws InvalidArgumentException
     * @throws InsufficientInventoryException
     */
    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $quantity, $expectedVersion) {
            $cart->refresh();

            if ($expectedVersion !== null && $cart->cart_version !== $expectedVersion) {
                throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
            }

            $variant = ProductVariant::with('product', 'inventoryItem')->find($variantId);

            if (! $variant) {
                throw new InvalidArgumentException('Variant not found.');
            }

            if ($variant->product->store_id !== $cart->store_id) {
                throw new InvalidArgumentException('Variant does not belong to this store.');
            }

            if ($variant->product->status !== ProductStatus::Active) {
                throw new InvalidArgumentException('Product is not active.');
            }

            if ($variant->status !== VariantStatus::Active) {
                throw new InvalidArgumentException('Variant is not active.');
            }

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();
            $totalQuantity = $existingLine ? $existingLine->quantity + $quantity : $quantity;

            if ($variant->inventoryItem && $variant->inventoryItem->policy === InventoryPolicy::Deny) {
                if ($variant->inventoryItem->quantityAvailable() < $totalQuantity) {
                    throw new InsufficientInventoryException(
                        $variantId,
                        $totalQuantity,
                        $variant->inventoryItem->quantityAvailable()
                    );
                }
            }

            $unitPrice = $variant->price_amount;

            if ($existingLine) {
                $existingLine->update([
                    'quantity' => $totalQuantity,
                    'unit_price_amount' => $unitPrice,
                    'line_subtotal_amount' => $unitPrice * $totalQuantity,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $unitPrice * $totalQuantity,
                ]);

                $cart->increment('cart_version');

                return $existingLine->fresh();
            }

            $line = $cart->lines()->create([
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price_amount' => $unitPrice,
                'line_subtotal_amount' => $unitPrice * $quantity,
                'line_discount_amount' => 0,
                'line_total_amount' => $unitPrice * $quantity,
            ]);

            $cart->increment('cart_version');

            return $line;
        });
    }

    /**
     * Update the quantity of a cart line. Removes the line if quantity is 0.
     *
     * @throws InvalidArgumentException
     * @throws InsufficientInventoryException
     */
    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion) {
            $cart->refresh();

            if ($expectedVersion !== null && $cart->cart_version !== $expectedVersion) {
                throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
            }

            $line = $cart->lines()->find($lineId);

            if (! $line) {
                throw new InvalidArgumentException('Cart line not found.');
            }

            if ($quantity <= 0) {
                $this->removeLine($cart, $lineId);

                return null;
            }

            $variant = ProductVariant::with('inventoryItem')->find($line->variant_id);

            if ($variant && $variant->inventoryItem && $variant->inventoryItem->policy === InventoryPolicy::Deny) {
                if ($variant->inventoryItem->quantityAvailable() < $quantity) {
                    throw new InsufficientInventoryException(
                        $line->variant_id,
                        $quantity,
                        $variant->inventoryItem->quantityAvailable()
                    );
                }
            }

            $line->update([
                'quantity' => $quantity,
                'line_subtotal_amount' => $line->unit_price_amount * $quantity,
                'line_discount_amount' => 0,
                'line_total_amount' => $line->unit_price_amount * $quantity,
            ]);

            $cart->increment('cart_version');

            return $line->fresh();
        });
    }

    /**
     * Remove a line from the cart.
     */
    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion) {
            $cart->refresh();

            if ($expectedVersion !== null && $cart->cart_version !== $expectedVersion) {
                throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
            }

            $line = $cart->lines()->find($lineId);

            if ($line) {
                $line->delete();
                $cart->increment('cart_version');
            }
        });
    }

    /**
     * Get or create a cart for the current session.
     */
    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $cartId = session('cart_id');

        if ($cartId) {
            $cart = Cart::where('id', $cartId)
                ->where('store_id', $store->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        if ($customer) {
            $cart = Cart::where('customer_id', $customer->id)
                ->where('store_id', $store->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                session(['cart_id' => $cart->id]);

                return $cart;
            }
        }

        $cart = $this->create($store, $customer);
        session(['cart_id' => $cart->id]);

        return $cart;
    }

    /**
     * Merge a guest cart into a customer cart on login.
     * Prefers the higher quantity for duplicate variants.
     */
    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart) {
            $guestCart->load('lines');
            $customerCart->load('lines');

            foreach ($guestCart->lines as $guestLine) {
                $existingLine = $customerCart->lines
                    ->firstWhere('variant_id', $guestLine->variant_id);

                if ($existingLine) {
                    $newQuantity = max($existingLine->quantity, $guestLine->quantity);
                    $existingLine->update([
                        'quantity' => $newQuantity,
                        'line_subtotal_amount' => $existingLine->unit_price_amount * $newQuantity,
                        'line_discount_amount' => 0,
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

            session(['cart_id' => $customerCart->id]);

            return $customerCart->fresh('lines');
        });
    }
}
