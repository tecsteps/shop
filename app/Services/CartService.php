<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartService
{
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::query()->create([
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
            $variant = ProductVariant::query()
                ->with('product')
                ->findOrFail($variantId);

            $this->validateVariantForCart($variant, $cart);
            $this->validateInventory($variant, $quantity);

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();

            if ($existingLine) {
                $newQuantity = $existingLine->quantity + $quantity;
                $this->validateInventory($variant, $newQuantity);

                return $this->updateLineAmounts($existingLine, $newQuantity, $variant->price_amount);
            }

            $subtotal = $variant->price_amount * $quantity;

            $line = CartLine::query()->create([
                'cart_id' => $cart->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $subtotal,
                'line_discount_amount' => 0,
                'line_total_amount' => $subtotal,
            ]);

            $this->incrementVersion($cart);

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

            $variant = ProductVariant::query()->findOrFail($line->variant_id);
            $this->validateInventory($variant, $quantity);

            return $this->updateLineAmounts($line, $quantity, $line->unit_price_amount);
        });
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId) {
            $cart->lines()->where('id', $lineId)->delete();
            $this->incrementVersion($cart);
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
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

        if ($customer) {
            $cart = Cart::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->latest()
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

    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart) {
            foreach ($guestCart->lines as $guestLine) {
                $existingLine = $customerCart->lines()
                    ->where('variant_id', $guestLine->variant_id)
                    ->first();

                if ($existingLine) {
                    $newQuantity = max($existingLine->quantity, $guestLine->quantity);
                    $this->updateLineAmounts($existingLine, $newQuantity, $existingLine->unit_price_amount);
                } else {
                    $guestLine->update(['cart_id' => $customerCart->id]);
                }
            }

            $guestCart->update(['status' => CartStatus::Abandoned]);
            $this->incrementVersion($customerCart);

            session(['cart_id' => $customerCart->id]);

            return $customerCart->fresh('lines');
        });
    }

    protected function validateVariantForCart(ProductVariant $variant, Cart $cart): void
    {
        $product = $variant->product;

        if (! $product || $product->store_id !== $cart->store_id) {
            throw new InvalidArgumentException('Variant does not belong to this store.');
        }

        if ($product->status !== ProductStatus::Active) {
            throw new InvalidArgumentException('Product is not active.');
        }

        if ($variant->status !== VariantStatus::Active) {
            throw new InvalidArgumentException('Variant is not active.');
        }
    }

    protected function validateInventory(ProductVariant $variant, int $quantity): void
    {
        $inventoryItem = InventoryItem::query()
            ->withoutGlobalScopes()
            ->where('variant_id', $variant->id)
            ->first();

        if (! $inventoryItem) {
            return;
        }

        if ($inventoryItem->policy === InventoryPolicy::Deny && $inventoryItem->availableQuantity() < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient inventory. Available: {$inventoryItem->availableQuantity()}, requested: {$quantity}."
            );
        }
    }

    protected function updateLineAmounts(CartLine $line, int $quantity, int $unitPrice): CartLine
    {
        $subtotal = $unitPrice * $quantity;

        $line->update([
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ]);

        $this->incrementVersion($line->cart);

        return $line->fresh();
    }

    protected function incrementVersion(Cart $cart): void
    {
        $cart->update([
            'cart_version' => $cart->cart_version + 1,
        ]);
    }
}
