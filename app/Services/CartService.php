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
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function create(Store $store, ?int $customerId = null): Cart
    {
        return Cart::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customerId,
            'currency' => $store->default_currency ?? 'USD',
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            $variant = ProductVariant::withoutGlobalScopes()->with(['product', 'inventoryItem'])->findOrFail($variantId);

            if ($variant->status !== VariantStatus::Active) {
                throw new \InvalidArgumentException('Variant is not active.');
            }

            if ($variant->product?->status !== ProductStatus::Active) {
                throw new \InvalidArgumentException('Product is not active.');
            }

            if ($variant->inventoryItem) {
                $inventoryItem = $variant->inventoryItem;
                if ($inventoryItem->policy === InventoryPolicy::Deny) {
                    $existingLine = $cart->lines()->where('variant_id', $variantId)->first();
                    $existingQty = $existingLine ? $existingLine->quantity : 0;
                    $totalQty = $existingQty + $quantity;
                    if ($inventoryItem->available() < $totalQty) {
                        throw new InsufficientInventoryException(
                            requested: $totalQty,
                            available: $inventoryItem->available(),
                        );
                    }
                }
            }

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();

            if ($existingLine) {
                $newQuantity = $existingLine->quantity + $quantity;
                $subtotal = $variant->price_amount * $newQuantity;

                $existingLine->update([
                    'quantity' => $newQuantity,
                    'unit_price_amount' => $variant->price_amount,
                    'line_subtotal_amount' => $subtotal,
                    'line_total_amount' => $subtotal,
                ]);

                $cart->update(['cart_version' => $cart->cart_version + 1]);

                /** @var CartLine */
                return $existingLine->fresh();
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

            $cart->update(['cart_version' => $cart->cart_version + 1]);

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): ?CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity): ?CartLine {
            $line = $cart->lines()->findOrFail($lineId);

            if ($quantity === 0) {
                $line->delete();
                $cart->update(['cart_version' => $cart->cart_version + 1]);

                return null;
            }

            $subtotal = $line->unit_price_amount * $quantity;

            $line->update([
                'quantity' => $quantity,
                'line_subtotal_amount' => $subtotal,
                'line_total_amount' => $subtotal - $line->line_discount_amount,
            ]);

            $cart->update(['cart_version' => $cart->cart_version + 1]);

            /** @var CartLine */
            return $line->fresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId): void {
            $line = $cart->lines()->findOrFail($lineId);
            $line->delete();
            $cart->update(['cart_version' => $cart->cart_version + 1]);
        });
    }

    public function getOrCreateForSession(Store $store, ?int $customerId = null): Cart
    {
        $cartId = Session::get('cart_id');

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

        $cart = $this->create($store, $customerId);
        Session::put('cart_id', $cart->id);

        return $cart;
    }

    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart): Cart {
            $guestLines = $guestCart->lines()->get();

            foreach ($guestLines as $guestLine) {
                $existingLine = $customerCart->lines()
                    ->where('variant_id', $guestLine->variant_id)
                    ->first();

                if ($existingLine) {
                    $newQuantity = $existingLine->quantity + $guestLine->quantity;
                    $subtotal = $existingLine->unit_price_amount * $newQuantity;

                    $existingLine->update([
                        'quantity' => $newQuantity,
                        'line_subtotal_amount' => $subtotal,
                        'line_total_amount' => $subtotal,
                    ]);
                } else {
                    $customerCart->lines()->create([
                        'variant_id' => $guestLine->variant_id,
                        'quantity' => $guestLine->quantity,
                        'unit_price_amount' => $guestLine->unit_price_amount,
                        'line_subtotal_amount' => $guestLine->line_subtotal_amount,
                        'line_discount_amount' => $guestLine->line_discount_amount,
                        'line_total_amount' => $guestLine->line_total_amount,
                    ]);
                }
            }

            $guestCart->update(['status' => CartStatus::Abandoned]);
            $customerCart->update(['cart_version' => $customerCart->cart_version + 1]);

            /** @var Cart */
            return $customerCart->fresh();
        });
    }

    public function validateVersion(Cart $cart, int $expectedVersion): void
    {
        if ($cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException(
                expected: $expectedVersion,
                actual: $cart->cart_version,
            );
        }
    }
}
