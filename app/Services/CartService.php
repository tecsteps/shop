<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCartException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

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

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        if ($quantity <= 0) {
            throw new InvalidCartException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity) {
            $variant = ProductVariant::with(['product', 'inventoryItem'])->find($variantId);

            if (! $variant) {
                throw new InvalidCartException('Variant not found.');
            }

            if ($variant->product->store_id !== $cart->store_id) {
                throw new InvalidCartException('Variant does not belong to this store.');
            }

            if ($variant->product->status !== ProductStatus::Active) {
                throw new InvalidCartException('Product is not active.');
            }

            if ($variant->status !== VariantStatus::Active) {
                throw new InvalidCartException('Variant is not active.');
            }

            $existingLine = $cart->lines()->where('variant_id', $variantId)->first();
            $totalQuantity = $existingLine ? $existingLine->quantity + $quantity : $quantity;

            if ($variant->inventoryItem) {
                if (! $this->inventoryService->checkAvailability($variant->inventoryItem, $totalQuantity)) {
                    throw new InsufficientInventoryException(
                        requested: $totalQuantity,
                        available: $variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved,
                    );
                }
            }

            if ($existingLine) {
                $existingLine->quantity = $totalQuantity;
                $existingLine->line_subtotal_amount = $existingLine->unit_price_amount * $totalQuantity;
                $existingLine->line_total_amount = $existingLine->line_subtotal_amount - $existingLine->line_discount_amount;
                $existingLine->save();

                $cart->increment('cart_version');

                return $existingLine;
            }

            $unitPrice = $variant->price_amount;
            $subtotal = $unitPrice * $quantity;

            $line = CartLine::create([
                'cart_id' => $cart->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price_amount' => $unitPrice,
                'line_subtotal_amount' => $subtotal,
                'line_discount_amount' => 0,
                'line_total_amount' => $subtotal,
            ]);

            $cart->increment('cart_version');

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): ?CartLine
    {
        if ($quantity < 0) {
            throw new InvalidCartException('Quantity must not be negative.');
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity) {
            $line = $cart->lines()->findOrFail($lineId);

            if ($quantity === 0) {
                $this->removeLine($cart, $lineId);

                return null;
            }

            $variant = $line->variant()->with('inventoryItem')->first();

            if ($variant->inventoryItem) {
                if (! $this->inventoryService->checkAvailability($variant->inventoryItem, $quantity)) {
                    throw new InsufficientInventoryException(
                        requested: $quantity,
                        available: $variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved,
                    );
                }
            }

            $line->quantity = $quantity;
            $line->line_subtotal_amount = $line->unit_price_amount * $quantity;
            $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
            $line->save();

            $cart->increment('cart_version');

            return $line;
        });
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId) {
            $cart->lines()->where('id', $lineId)->delete();
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
            $guestLines = $guestCart->lines()->with('variant')->get();

            foreach ($guestLines as $guestLine) {
                $existingLine = $customerCart->lines()
                    ->where('variant_id', $guestLine->variant_id)
                    ->first();

                if ($existingLine) {
                    $existingLine->quantity = max($existingLine->quantity, $guestLine->quantity);
                    $existingLine->line_subtotal_amount = $existingLine->unit_price_amount * $existingLine->quantity;
                    $existingLine->line_total_amount = $existingLine->line_subtotal_amount - $existingLine->line_discount_amount;
                    $existingLine->save();
                } else {
                    CartLine::create([
                        'cart_id' => $customerCart->id,
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
            $customerCart->load('lines');

            return $customerCart;
        });
    }
}
