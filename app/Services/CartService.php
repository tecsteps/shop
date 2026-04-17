<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency ?? 'USD',
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $quantity) {
            $variant = ProductVariant::withoutGlobalScopes()
                ->with(['product' => fn ($q) => $q->withoutGlobalScopes(), 'inventoryItem' => fn ($q) => $q->withoutGlobalScopes()])
                ->findOrFail($variantId);

            if ($variant->product->store_id !== $cart->store_id) {
                throw new \InvalidArgumentException('Variant does not belong to this store.');
            }

            if ($variant->product->status !== ProductStatus::Active) {
                throw new \InvalidArgumentException('Product is not active.');
            }

            if ($variant->status !== VariantStatus::Active) {
                throw new \InvalidArgumentException('Variant is not active.');
            }

            if ($variant->inventoryItem && $variant->inventoryItem->policy === InventoryPolicy::Deny) {
                if ($variant->inventoryItem->available < $quantity) {
                    throw new InsufficientInventoryException(
                        "Insufficient inventory: requested {$quantity}, available {$variant->inventoryItem->available}."
                    );
                }
            }

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();

            if ($existingLine) {
                $newQuantity = $existingLine->quantity + $quantity;

                if ($variant->inventoryItem && $variant->inventoryItem->policy === InventoryPolicy::Deny) {
                    if ($variant->inventoryItem->available < $newQuantity) {
                        throw new InsufficientInventoryException(
                            "Insufficient inventory: requested {$newQuantity}, available {$variant->inventoryItem->available}."
                        );
                    }
                }

                $subtotal = $variant->price_amount * $newQuantity;
                $existingLine->update([
                    'quantity' => $newQuantity,
                    'line_subtotal_amount' => $subtotal,
                    'line_total_amount' => $subtotal - $existingLine->line_discount_amount,
                ]);

                $cart->increment('cart_version');

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

            $cart->increment('cart_version');

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity) {
            $line = $cart->lines()->findOrFail($lineId);

            if ($quantity <= 0) {
                $this->removeLine($cart, $lineId);

                return $line;
            }

            $variant = $line->variant()->with('inventoryItem')->first();

            if ($variant->inventoryItem && $variant->inventoryItem->policy === InventoryPolicy::Deny) {
                if ($variant->inventoryItem->available < $quantity) {
                    throw new InsufficientInventoryException(
                        "Insufficient inventory: requested {$quantity}, available {$variant->inventoryItem->available}."
                    );
                }
            }

            $subtotal = $line->unit_price_amount * $quantity;

            $line->update([
                'quantity' => $quantity,
                'line_subtotal_amount' => $subtotal,
                'line_total_amount' => $subtotal - $line->line_discount_amount,
            ]);

            $cart->increment('cart_version');

            return $line->fresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId) {
            $cart->lines()->findOrFail($lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        if ($customer) {
            $cart = Cart::where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

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

        $cart = $this->create($store, $customer);

        if (! $customer) {
            session(['cart_id' => $cart->id]);
        }

        return $cart;
    }

    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart) {
            foreach ($guestCart->lines as $guestLine) {
                $existingLine = $customerCart->lines()
                    ->where('variant_id', $guestLine->variant_id)
                    ->first();

                if ($existingLine) {
                    $newQuantity = max($existingLine->quantity, $guestLine->quantity);
                    $subtotal = $existingLine->unit_price_amount * $newQuantity;
                    $existingLine->update([
                        'quantity' => $newQuantity,
                        'line_subtotal_amount' => $subtotal,
                        'line_total_amount' => $subtotal - $existingLine->line_discount_amount,
                    ]);
                } else {
                    $guestLine->update(['cart_id' => $customerCart->id]);
                }
            }

            $guestCart->update(['status' => CartStatus::Abandoned]);
            $customerCart->increment('cart_version');

            session()->forget('cart_id');

            return $customerCart->fresh()->load('lines');
        });
    }
}
