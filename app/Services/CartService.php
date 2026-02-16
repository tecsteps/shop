<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\Store;

class CartService
{
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'session_id' => session()->getId(),
            'status' => CartStatus::Active,
            'currency' => $store->currency ?? 'USD',
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $qty): CartLine
    {
        $variant = \App\Models\ProductVariant::with(['product', 'inventoryItem'])->findOrFail($variantId);

        if ($variant->product->status !== ProductStatus::Active) {
            throw new \InvalidArgumentException('Product is not active.');
        }

        $inventory = $variant->inventoryItem;
        if ($inventory && $inventory->policy === InventoryPolicy::Deny) {
            $existingQty = $cart->lines()->where('variant_id', $variantId)->value('quantity') ?? 0;
            $totalNeeded = $existingQty + $qty;
            if ($inventory->quantity_available < $totalNeeded) {
                throw new InsufficientInventoryException(
                    requested: $totalNeeded,
                    available: $inventory->quantity_available,
                );
            }
        }

        $existing = $cart->lines()->where('variant_id', $variantId)->first();
        if ($existing) {
            $existing->quantity += $qty;
            $existing->unit_price = $variant->price_amount;
            $existing->subtotal = $existing->quantity * $variant->price_amount;
            $existing->total = $existing->subtotal;
            $existing->save();
            $cart->increment('cart_version');

            return $existing;
        }

        $line = $cart->lines()->create([
            'variant_id' => $variantId,
            'quantity' => $qty,
            'unit_price' => $variant->price_amount,
            'subtotal' => $qty * $variant->price_amount,
            'total' => $qty * $variant->price_amount,
        ]);

        $cart->increment('cart_version');

        return $line;
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $qty): CartLine
    {
        $line = $cart->lines()->with('variant.inventoryItem')->findOrFail($lineId);

        if ($qty <= 0) {
            $line->delete();
            $cart->increment('cart_version');

            return $line;
        }

        $inventory = $line->variant?->inventoryItem;
        if ($inventory && $inventory->policy === InventoryPolicy::Deny && $inventory->quantity_available < $qty) {
            throw new InsufficientInventoryException(
                requested: $qty,
                available: $inventory->quantity_available,
            );
        }

        $line->quantity = $qty;
        $line->subtotal = $qty * $line->unit_price;
        $line->total = $line->subtotal;
        $line->save();

        $cart->increment('cart_version');

        return $line;
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        $cart->lines()->findOrFail($lineId)->delete();
        $cart->increment('cart_version');
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $sessionId = session()->getId();

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active)
            ->when($customer, fn ($q) => $q->where('customer_id', $customer->id))
            ->when(! $customer, fn ($q) => $q->where('session_id', $sessionId)->whereNull('customer_id'))
            ->first();

        if ($cart) {
            return $cart;
        }

        return $this->create($store, $customer);
    }

    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        foreach ($guestCart->lines as $guestLine) {
            $existing = $customerCart->lines()->where('variant_id', $guestLine->variant_id)->first();
            if ($existing) {
                $existing->quantity += $guestLine->quantity;
                $existing->subtotal = $existing->quantity * $existing->unit_price;
                $existing->total = $existing->subtotal;
                $existing->save();
            } else {
                $customerCart->lines()->create($guestLine->only([
                    'variant_id', 'quantity', 'unit_price', 'subtotal', 'total',
                ]));
            }
        }

        $guestCart->update(['status' => CartStatus::Abandoned]);
        $customerCart->increment('cart_version');

        return $customerCart;
    }
}
