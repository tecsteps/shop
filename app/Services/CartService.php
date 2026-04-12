<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\Store;
use InvalidArgumentException;
use RuntimeException;

class CartService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function create(Store $store, ?object $customer = null, ?string $sessionId = null): Cart
    {
        $cart = new Cart;
        $cart->store_id = $store->id;
        $cart->customer_id = $customer?->id;
        $cart->session_id = $sessionId;
        $cart->currency = (string) ($store->default_currency ?? 'USD');
        $cart->cart_version = 1;
        $cart->status = CartStatus::Active->value;
        $cart->save();

        return $cart;
    }

    public function getOrCreateForSession(Store $store, ?object $customer = null, ?string $sessionId = null): Cart
    {
        $query = Cart::query()
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active->value);

        if ($customer !== null) {
            $existing = (clone $query)->where('customer_id', $customer->id)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        if ($sessionId !== null) {
            $existing = (clone $query)->where('session_id', $sessionId)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->create($store, $customer, $sessionId);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $variant = ProductVariant::query()
            ->with(['product', 'inventoryItem'])
            ->find($variantId);

        if ($variant === null) {
            throw new RuntimeException("Variant {$variantId} not found.");
        }

        if ($variant->status !== VariantStatus::Active) {
            throw new RuntimeException("Variant {$variantId} is not active.");
        }

        $product = $variant->product;
        if ($product === null || $product->status !== ProductStatus::Active) {
            throw new RuntimeException("Product for variant {$variantId} is not active.");
        }

        $existing = $cart->lines()->where('variant_id', $variantId)->first();
        $targetQty = ($existing?->quantity ?? 0) + $quantity;

        $inventoryItem = $variant->inventoryItem;
        if ($inventoryItem !== null && ! $this->inventoryService->checkAvailability($inventoryItem, $targetQty)) {
            throw new RuntimeException("Insufficient inventory for variant {$variantId}.");
        }

        if ($existing !== null) {
            $existing->quantity = $targetQty;
            $existing->line_subtotal_amount = (int) $existing->unit_price_amount * $targetQty;
            $existing->line_total_amount = $existing->line_subtotal_amount - (int) $existing->line_discount_amount;
            $existing->save();
            $this->touchVersion($cart);

            return $existing;
        }

        $unitPrice = (int) $variant->price_amount;
        $line = new CartLine;
        $line->cart_id = $cart->id;
        $line->variant_id = $variantId;
        $line->quantity = $quantity;
        $line->unit_price_amount = $unitPrice;
        $line->line_subtotal_amount = $unitPrice * $quantity;
        $line->line_discount_amount = 0;
        $line->line_total_amount = $unitPrice * $quantity;
        $line->save();

        $this->touchVersion($cart);

        return $line;
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): CartLine
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $line = $cart->lines()->findOrFail($lineId);

        $variant = ProductVariant::query()->with('inventoryItem')->findOrFail($line->variant_id);
        $inventoryItem = $variant->inventoryItem;
        if ($inventoryItem !== null && ! $this->inventoryService->checkAvailability($inventoryItem, $quantity)) {
            throw new RuntimeException("Insufficient inventory for variant {$line->variant_id}.");
        }

        $line->quantity = $quantity;
        $line->line_subtotal_amount = (int) $line->unit_price_amount * $quantity;
        $line->line_total_amount = $line->line_subtotal_amount - (int) $line->line_discount_amount;
        $line->save();

        $this->touchVersion($cart);

        return $line;
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        $line = $cart->lines()->findOrFail($lineId);
        $line->delete();

        $this->touchVersion($cart);
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        foreach ($guest->lines()->get() as $guestLine) {
            $existing = $customer->lines()->where('variant_id', $guestLine->variant_id)->first();

            if ($existing !== null) {
                $newQty = (int) $existing->quantity + (int) $guestLine->quantity;
                $existing->quantity = $newQty;
                $existing->line_subtotal_amount = (int) $existing->unit_price_amount * $newQty;
                $existing->line_total_amount = $existing->line_subtotal_amount - (int) $existing->line_discount_amount;
                $existing->save();
            } else {
                $copy = $guestLine->replicate();
                $copy->cart_id = $customer->id;
                $copy->save();
            }
        }

        $guest->lines()->delete();
        $guest->status = CartStatus::Abandoned->value;
        $guest->save();

        $this->touchVersion($customer);

        return $customer;
    }

    private function touchVersion(Cart $cart): void
    {
        $cart->incrementVersion();
        $cart->save();
    }
}
