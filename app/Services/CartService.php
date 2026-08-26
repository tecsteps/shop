<?php

namespace App\Services;

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
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => 'active',
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $qty): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $qty) {
            $variant = ProductVariant::with('product', 'inventoryItem')->findOrFail($variantId);

            if ($variant->product->store_id !== $cart->store_id) {
                throw new InvalidArgumentException('Variant does not belong to this store.');
            }

            if ($variant->product->status !== 'active') {
                throw new InvalidArgumentException('Product is not active.');
            }

            if ($variant->status !== 'active') {
                throw new InvalidArgumentException('Variant is not active.');
            }

            $existing = $cart->lines()->where('variant_id', $variantId)->first();
            $newQuantity = ($existing?->quantity ?? 0) + $qty;

            $inventory = $variant->inventoryItem;

            if ($inventory && $inventory->policy === 'deny' && ! $this->inventoryService->checkAvailability($inventory, $newQuantity)) {
                throw new InsufficientInventoryException('The selected variant is out of stock.');
            }

            if ($existing) {
                $existing->update([
                    'quantity' => $newQuantity,
                    'unit_price_amount' => $variant->price_amount,
                    'line_subtotal_amount' => $variant->price_amount * $newQuantity,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $variant->price_amount * $newQuantity,
                ]);

                $this->bumpVersion($cart);

                return $existing;
            }

            $line = $cart->lines()->create([
                'variant_id' => $variantId,
                'quantity' => $qty,
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $variant->price_amount * $qty,
                'line_discount_amount' => 0,
                'line_total_amount' => $variant->price_amount * $qty,
            ]);

            $this->bumpVersion($cart);

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $qty): CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $qty) {
            if ($qty <= 0) {
                $this->removeLine($cart, $lineId);

                return new CartLine(['cart_id' => $cart->id]);
            }

            $line = $cart->lines()->findOrFail($lineId);
            $variant = ProductVariant::with('inventoryItem')->findOrFail($line->variant_id);

            $inventory = $variant->inventoryItem;

            if ($inventory && $inventory->policy === 'deny' && ! $this->inventoryService->checkAvailability($inventory, $qty)) {
                throw new InsufficientInventoryException('The selected variant is out of stock.');
            }

            $line->update([
                'quantity' => $qty,
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $variant->price_amount * $qty,
                'line_discount_amount' => 0,
                'line_total_amount' => $variant->price_amount * $qty,
            ]);

            $this->bumpVersion($cart);

            return $line;
        });
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId) {
            $cart->lines()->whereKey($lineId)->delete();
            $this->bumpVersion($cart);
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $cartId = session('cart_id');

        if ($cartId) {
            $cart = Cart::find($cartId);

            if ($cart && $cart->store_id === $store->id && $cart->status === 'active') {
                return $cart;
            }
        }

        $cart = $this->create($store, $customer);
        session(['cart_id' => $cart->id]);

        return $cart;
    }

    public function assertVersion(Cart $cart, int $expectedVersion): void
    {
        if ($cart->cart_version !== $expectedVersion) {
            throw new \App\Exceptions\CartVersionMismatchException('The cart has been modified.');
        }
    }

    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart) {
            foreach ($guestCart->lines()->with('variant')->get() as $line) {
                $existing = $customerCart->lines()->where('variant_id', $line->variant_id)->first();

                if ($existing) {
                    $quantity = max($existing->quantity, $line->quantity);
                    $existing->update([
                        'quantity' => $quantity,
                        'line_subtotal_amount' => $existing->unit_price_amount * $quantity,
                        'line_discount_amount' => 0,
                        'line_total_amount' => $existing->unit_price_amount * $quantity,
                    ]);
                } else {
                    $customerCart->lines()->create([
                        'variant_id' => $line->variant_id,
                        'quantity' => $line->quantity,
                        'unit_price_amount' => $line->unit_price_amount,
                        'line_subtotal_amount' => $line->unit_price_amount * $line->quantity,
                        'line_discount_amount' => 0,
                        'line_total_amount' => $line->unit_price_amount * $line->quantity,
                    ]);
                }
            }

            $guestCart->update(['status' => 'abandoned']);
            $this->bumpVersion($customerCart);

            session()->forget('cart_id');

            return $customerCart->fresh();
        });
    }

    private function bumpVersion(Cart $cart): void
    {
        $cart->increment('cart_version');
    }
}
