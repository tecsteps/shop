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
        return Cart::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity = 1): CartLine
    {
        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            $variant = ProductVariant::with('product', 'inventoryItem')->findOrFail($variantId);

            $this->validateVariantForCart($variant, $cart);

            if ($variant->inventoryItem?->policy === InventoryPolicy::Deny) {
                $available = $variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved;

                if ($available < $quantity) {
                    throw new InsufficientInventoryException(
                        "Insufficient inventory. Available: {$available}, Requested: {$quantity}"
                    );
                }
            }

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();

            if ($existingLine) {
                $newQuantity = $existingLine->quantity + $quantity;

                if ($variant->inventoryItem?->policy === InventoryPolicy::Deny) {
                    $available = $variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved;

                    if ($available < $newQuantity) {
                        throw new InsufficientInventoryException(
                            "Insufficient inventory. Available: {$available}, Requested: {$newQuantity}"
                        );
                    }
                }

                $subtotal = $existingLine->unit_price_amount * $newQuantity;
                $existingLine->update([
                    'quantity' => $newQuantity,
                    'line_subtotal_amount' => $subtotal,
                    'line_total_amount' => $subtotal - $existingLine->line_discount_amount,
                ]);

                $cart->increment('cart_version');

                return $existingLine->fresh();
            }

            $unitPrice = $variant->price_amount;
            $subtotal = $unitPrice * $quantity;

            $line = $cart->lines()->create([
                'variant_id' => $variantId,
                'product_title' => $variant->product->title,
                'variant_title' => $this->buildVariantTitle($variant),
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'unit_price_amount' => $unitPrice,
                'line_subtotal_amount' => $subtotal,
                'line_discount_amount' => 0,
                'line_total_amount' => $subtotal,
                'requires_shipping' => $variant->requires_shipping,
            ]);

            $cart->increment('cart_version');

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity): CartLine {
            $line = $cart->lines()->findOrFail($lineId);

            if ($quantity <= 0) {
                $this->removeLine($cart, $lineId);

                return $line;
            }

            $variant = ProductVariant::with('inventoryItem')->find($line->variant_id);

            if ($variant?->inventoryItem?->policy === InventoryPolicy::Deny) {
                $available = $variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved;

                if ($available < $quantity) {
                    throw new InsufficientInventoryException(
                        "Insufficient inventory. Available: {$available}, Requested: {$quantity}"
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
        DB::transaction(function () use ($cart, $lineId): void {
            $cart->lines()->where('id', $lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    public function getOrCreateForSession(Store $store, ?string $sessionCartId, ?Customer $customer = null): Cart
    {
        if ($customer) {
            $cart = Cart::where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->latest()
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        if ($sessionCartId) {
            $cart = Cart::find($sessionCartId);

            if ($cart && $cart->status === CartStatus::Active) {
                return $cart;
            }
        }

        return $this->create($store, $customer);
    }

    public function mergeOnLogin(Cart $guestCart, Customer $customer): Cart
    {
        return DB::transaction(function () use ($guestCart, $customer): Cart {
            $customerCart = Cart::where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->where('id', '!=', $guestCart->id)
                ->latest()
                ->first();

            if (! $customerCart) {
                $guestCart->update(['customer_id' => $customer->id]);

                return $guestCart;
            }

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

            return $customerCart->fresh();
        });
    }

    private function validateVariantForCart(ProductVariant $variant, Cart $cart): void
    {
        if (! $variant->product || $variant->product->store_id !== $cart->store_id) {
            throw new InvalidArgumentException('Variant does not belong to the current store.');
        }

        if ($variant->product->status !== ProductStatus::Active) {
            throw new InvalidArgumentException('Product is not active.');
        }

        if ($variant->status !== VariantStatus::Active) {
            throw new InvalidArgumentException('Variant is not active.');
        }
    }

    private function buildVariantTitle(ProductVariant $variant): ?string
    {
        if ($variant->is_default) {
            return null;
        }

        $variant->loadMissing('optionValues');

        return $variant->optionValues->pluck('value')->implode(' / ') ?: null;
    }
}
