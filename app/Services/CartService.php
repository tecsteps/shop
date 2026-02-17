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

class CartService
{
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

    public function addLine(Cart $cart, int $variantId, int $qty, ?int $expectedVersion = null): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $qty, $expectedVersion) {
            $this->checkVersion($cart, $expectedVersion);

            $variant = ProductVariant::with(['product', 'inventoryItem'])->findOrFail($variantId);
            $product = $variant->product;

            if (! $product || $product->store_id !== $cart->store_id) {
                throw new \InvalidArgumentException('Variant does not belong to this store');
            }

            if ($product->status !== ProductStatus::Active) {
                throw new \InvalidArgumentException('Product is not active');
            }

            if ($variant->status !== VariantStatus::Active) {
                throw new \InvalidArgumentException('Variant is not active');
            }

            $inventoryItem = $variant->inventoryItem;
            if ($inventoryItem && $inventoryItem->policy === InventoryPolicy::Deny) {
                $available = $inventoryItem->quantity_on_hand - $inventoryItem->quantity_reserved;
                if ($available < $qty) {
                    throw new InsufficientInventoryException(
                        requested: $qty,
                        available: $available,
                    );
                }
            }

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();

            if ($existingLine) {
                $newQty = $existingLine->quantity + $qty;

                if ($inventoryItem && $inventoryItem->policy === InventoryPolicy::Deny) {
                    $available = $inventoryItem->quantity_on_hand - $inventoryItem->quantity_reserved;
                    if ($available < $newQty) {
                        throw new InsufficientInventoryException(
                            requested: $newQty,
                            available: $available,
                        );
                    }
                }

                $existingLine->update([
                    'quantity' => $newQty,
                    'line_subtotal_amount' => $variant->price_amount * $newQty,
                    'line_total_amount' => $variant->price_amount * $newQty,
                ]);

                $cart->increment('cart_version');

                return $existingLine->fresh();
            }

            $subtotal = $variant->price_amount * $qty;
            $line = $cart->lines()->create([
                'variant_id' => $variantId,
                'quantity' => $qty,
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $subtotal,
                'line_discount_amount' => 0,
                'line_total_amount' => $subtotal,
            ]);

            $cart->increment('cart_version');

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $qty, ?int $expectedVersion = null): CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $qty, $expectedVersion) {
            $this->checkVersion($cart, $expectedVersion);

            $line = $cart->lines()->findOrFail($lineId);

            if ($qty <= 0) {
                $this->removeLine($cart, $lineId, $expectedVersion);

                return $line;
            }

            $variant = $line->variant()->with('inventoryItem')->first();
            $inventoryItem = $variant->inventoryItem;

            if ($inventoryItem && $inventoryItem->policy === InventoryPolicy::Deny) {
                $available = $inventoryItem->quantity_on_hand - $inventoryItem->quantity_reserved;
                if ($available < $qty) {
                    throw new InsufficientInventoryException(
                        requested: $qty,
                        available: $available,
                    );
                }
            }

            $subtotal = $line->unit_price_amount * $qty;
            $line->update([
                'quantity' => $qty,
                'line_subtotal_amount' => $subtotal,
                'line_total_amount' => $subtotal - $line->line_discount_amount,
            ]);

            $cart->increment('cart_version');

            return $line->fresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion) {
            $this->checkVersion($cart, $expectedVersion);

            $cart->lines()->findOrFail($lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        if ($customer) {
            $cart = Cart::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        $cartId = session('cart_id');
        if ($cartId) {
            $cart = Cart::query()
                ->withoutGlobalScopes()
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
                    $newQty = max($existingLine->quantity, $guestLine->quantity);
                    $existingLine->update([
                        'quantity' => $newQty,
                        'line_subtotal_amount' => $existingLine->unit_price_amount * $newQty,
                        'line_total_amount' => $existingLine->unit_price_amount * $newQty,
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
            session()->forget('cart_id');

            return $customerCart->fresh()->load('lines');
        });
    }

    private function checkVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null) {
            $cart->refresh();
            if ($cart->cart_version !== $expectedVersion) {
                throw new CartVersionMismatchException(
                    expectedVersion: $expectedVersion,
                    actualVersion: $cart->cart_version,
                );
            }
        }
    }
}
