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
use InvalidArgumentException;

class CartService
{
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

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $quantity) {
            $variant = ProductVariant::with(['product', 'inventoryItem'])->findOrFail($variantId);

            if ($variant->product->status !== ProductStatus::Active) {
                throw new InvalidArgumentException('Product is not active.');
            }

            if ($variant->status !== VariantStatus::Active) {
                throw new InvalidArgumentException('Variant is not active.');
            }

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();
            $totalQuantity = $existingLine ? $existingLine->quantity + $quantity : $quantity;

            if ($variant->inventoryItem
                && $variant->inventoryItem->policy === InventoryPolicy::Deny
                && $variant->inventoryItem->quantity_available < $totalQuantity
            ) {
                throw new InsufficientInventoryException(
                    "Insufficient inventory: available {$variant->inventoryItem->quantity_available}, requested {$totalQuantity}."
                );
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
                $line = $existingLine;
            } else {
                $line = $cart->lines()->create([
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_price_amount' => $unitPrice,
                    'line_subtotal_amount' => $unitPrice * $quantity,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $unitPrice * $quantity,
                ]);
            }

            $cart->increment('cart_version');

            return $line->fresh();
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): ?CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity) {
            $line = $cart->lines()->findOrFail($lineId);

            if ($quantity <= 0) {
                $this->removeLine($cart, $lineId);

                return null;
            }

            $variant = $line->variant()->with('inventoryItem')->first();

            if ($variant->inventoryItem
                && $variant->inventoryItem->policy === InventoryPolicy::Deny
                && $variant->inventoryItem->quantity_available < $quantity
            ) {
                throw new InsufficientInventoryException(
                    "Insufficient inventory: available {$variant->inventoryItem->quantity_available}, requested {$quantity}."
                );
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

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId) {
            $cart->lines()->findOrFail($lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
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

        $cart = $this->create($store, $customer);
        session(['cart_id' => $cart->id]);

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
                    $existingLine->update([
                        'quantity' => $newQuantity,
                        'line_subtotal_amount' => $existingLine->unit_price_amount * $newQuantity,
                        'line_total_amount' => $existingLine->unit_price_amount * $newQuantity,
                    ]);
                } else {
                    $guestLine->update(['cart_id' => $customerCart->id]);
                }
            }

            $guestCart->update(['status' => CartStatus::Abandoned]);
            $customerCart->increment('cart_version');

            return $customerCart->fresh(['lines']);
        });
    }
}
