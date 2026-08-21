<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::withoutGlobalScopes()->create(['store_id' => $store->getKey(), 'customer_id' => $customer?->getKey(), 'currency' => $store->default_currency, 'cart_version' => 1, 'status' => 'active']);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        if ($quantity < 1 || $quantity > 9999) {
            throw new \InvalidArgumentException('Quantity must be between 1 and 9999.');
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            $cart = Cart::withoutGlobalScopes()->lockForUpdate()->with('lines')->findOrFail($cart->getKey());
            $variant = ProductVariant::with(['product', 'inventory'])->findOrFail($variantId);

            if ($variant->product->store_id !== $cart->store_id || $variant->product->status !== ProductStatus::Active || $variant->status !== VariantStatus::Active) {
                abort(404);
            }

            $line = $cart->lines->firstWhere('variant_id', $variant->getKey());
            $newQuantity = ($line?->quantity ?? 0) + $quantity;

            if ($variant->inventory !== null && ! $variant->inventory->canSell($newQuantity)) {
                throw new InsufficientInventoryException;
            }

            $line ??= new CartLine(['cart_id' => $cart->getKey(), 'variant_id' => $variant->getKey()]);
            $line->fill([
                'quantity' => $newQuantity,
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $variant->price_amount * $newQuantity,
                'line_discount_amount' => 0,
                'line_total_amount' => $variant->price_amount * $newQuantity,
            ])->save();
            $cart->increment('cart_version');

            return $line->load('variant.product');
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): CartLine
    {
        if ($quantity < 1 || $quantity > 9999) {
            throw new \InvalidArgumentException('Quantity must be between 1 and 9999.');
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity): CartLine {
            $line = $cart->lines()->with(['variant.inventory'])->findOrFail($lineId);

            if ($line->variant->inventory !== null && ! $line->variant->inventory->canSell($quantity)) {
                throw new InsufficientInventoryException;
            }

            $line->update(['quantity' => $quantity, 'line_subtotal_amount' => $line->unit_price_amount * $quantity, 'line_total_amount' => $line->unit_price_amount * $quantity]);
            $cart->increment('cart_version');

            return $line->refresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId): void {
            $cart->lines()->findOrFail($lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    public function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $expectedVersion !== $cart->cart_version) {
            throw new CartVersionConflictException;
        }
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $key = 'cart_id_'.$store->getKey();
        $cart = $customer?->carts()->where('status', 'active')->latest()->first();

        if ($cart === null) {
            $sessionCartId = session($key, session('cart_id'));
            $cart = Cart::withoutGlobalScopes()->whereKey($sessionCartId)->where('store_id', $store->getKey())->where('status', 'active')->first();
        }

        $cart ??= $this->create($store, $customer);
        session([$key => $cart->getKey(), 'cart_id' => $cart->getKey()]);

        return $cart->load(['lines.variant.product', 'lines.variant.inventory']);
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        if ($guest->store_id !== $customer->store_id) {
            throw new \InvalidArgumentException('Carts must belong to the same store.');
        }

        DB::transaction(function () use ($guest, $customer): void {
            $guest = Cart::withoutGlobalScopes()->lockForUpdate()->with('lines.variant.product')->findOrFail($guest->getKey());
            $customer = Cart::withoutGlobalScopes()->lockForUpdate()->with('lines')->findOrFail($customer->getKey());
            $customerLines = $customer->lines->keyBy('variant_id');

            foreach ($guest->lines as $guestLine) {
                $variant = $guestLine->variant;

                if ($variant === null || $variant->product === null || $variant->product->status !== ProductStatus::Active || $variant->status !== VariantStatus::Active) {
                    continue;
                }

                $customerLine = $customerLines->get($guestLine->variant_id);
                $quantity = max((int) ($customerLine?->quantity ?? 0), (int) $guestLine->quantity);

                if ($variant->inventory !== null && ! $variant->inventory->canSell($quantity)) {
                    continue;
                }

                $attributes = [
                    'quantity' => $quantity,
                    'unit_price_amount' => $variant->price_amount,
                    'line_subtotal_amount' => $variant->price_amount * $quantity,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $variant->price_amount * $quantity,
                ];

                if ($customerLine === null) {
                    $customerLine = $customer->lines()->create(['variant_id' => $variant->getKey(), ...$attributes]);
                    $customerLines->put($variant->getKey(), $customerLine);
                } else {
                    $customerLine->update($attributes);
                }

                $customer->increment('cart_version');
            }

            $guest->update(['status' => 'abandoned']);
            session()->forget(['cart_id_'.$guest->store_id, 'cart_id']);
        });

        return $customer->refresh()->load('lines');
    }
}
