<?php

namespace App\Services\Shop;

use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PricingService $pricing,
    ) {}

    public function current(): Cart
    {
        $store = app('current_store');
        $cartId = session('cart_id');

        $cart = $cartId
            ? Cart::query()->with('lines.variant.product.media')->find($cartId)
            : null;

        if (! $cart || $cart->store_id !== $store->id || $cart->status !== 'active') {
            $cart = Cart::query()->create([
                'store_id' => $store->id,
                'customer_id' => auth('customer')->id(),
                'currency' => $store->default_currency,
            ]);

            session(['cart_id' => $cart->id]);
        }

        return $cart->load('lines.variant.product.media');
    }

    public function add(ProductVariant $variant, int $quantity = 1): Cart
    {
        return DB::transaction(function () use ($variant, $quantity): Cart {
            $cart = $this->current();
            $line = $cart->lines()->where('product_variant_id', $variant->id)->first();
            $newQuantity = $quantity + ($line?->quantity ?? 0);

            $this->inventory->assertPurchasable($variant, $newQuantity);

            $snapshot = [
                'product_title' => $variant->product->title,
                'variant_title' => $variant->optionValues->pluck('value')->implode(' / '),
                'handle' => $variant->product->handle,
                'image' => $variant->product->media->first()?->url,
            ];

            $cart->lines()->updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'quantity' => $newQuantity,
                    'unit_price_amount' => $variant->price_amount,
                    'snapshot_json' => $snapshot,
                ],
            );

            $cart->increment('cart_version');

            return $cart->refresh()->load('lines.variant.product.media');
        });
    }

    public function updateLine(int $lineId, int $quantity): Cart
    {
        return DB::transaction(function () use ($lineId, $quantity): Cart {
            $cart = $this->current();
            $line = $cart->lines()->whereKey($lineId)->firstOrFail();

            if ($quantity <= 0) {
                $line->delete();
            } else {
                $this->inventory->assertPurchasable($line->variant, $quantity);
                $line->update(['quantity' => $quantity]);
            }

            $cart->increment('cart_version');

            return $cart->refresh()->load('lines.variant.product.media');
        });
    }

    public function applyDiscount(string $code): Cart
    {
        $cart = $this->current();
        $cart->update(['discount_code' => strtoupper($code)]);

        $this->pricing->cartTotals($cart);
        $cart->increment('cart_version');

        return $cart->refresh()->load('lines.variant.product.media');
    }

    public function removeDiscount(): Cart
    {
        $cart = $this->current();
        $cart->update(['discount_code' => null]);
        $cart->increment('cart_version');

        return $cart->refresh()->load('lines.variant.product.media');
    }
}

