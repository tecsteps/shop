<?php

namespace App\Services;

use App\Enums\CartStatus;
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
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::query()->create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        $this->assertExpectedVersion($cart, $expectedVersion);

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            if ($quantity < 1) {
                throw ValidationException::withMessages(['quantity' => 'Quantity must be at least one.']);
            }

            $variant = ProductVariant::query()->with(['product', 'inventoryItem'])->findOrFail($variantId);
            $productStatus = $variant->product->status instanceof ProductStatus ? $variant->product->status : ProductStatus::from($variant->product->status);
            $variantStatus = $variant->status instanceof VariantStatus ? $variant->status : VariantStatus::from($variant->status);

            if ($variant->product->store_id !== $cart->store_id || $productStatus !== ProductStatus::Active || $variantStatus !== VariantStatus::Active) {
                throw ValidationException::withMessages(['variant_id' => 'This product variant is not available.']);
            }

            $line = $cart->lines()->where('variant_id', $variant->id)->first();
            $newQuantity = ($line?->quantity ?? 0) + $quantity;
            $this->ensureAvailable($variant, $newQuantity);

            $line ??= new CartLine(['cart_id' => $cart->id, 'variant_id' => $variant->id]);
            $this->setLineAmounts($line, $variant->price_amount, $newQuantity);
            $line->save();
            $cart->increment('cart_version');

            return $line->refresh();
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        $this->assertExpectedVersion($cart, $expectedVersion);

        if ($quantity === 0) {
            $this->removeLine($cart, $lineId);

            return null;
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity): CartLine {
            if ($quantity < 0) {
                throw ValidationException::withMessages(['quantity' => 'Quantity cannot be negative.']);
            }

            $line = $cart->lines()->with('variant.inventoryItem')->findOrFail($lineId);
            $this->ensureAvailable($line->variant, $quantity);
            $this->setLineAmounts($line, $line->variant->price_amount, $quantity);
            $line->save();
            $cart->increment('cart_version');

            return $line->refresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        $this->assertExpectedVersion($cart, $expectedVersion);

        DB::transaction(function () use ($cart, $lineId): void {
            $cart->lines()->findOrFail($lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $cart = $customer?->carts()->where('status', CartStatus::Active)->latest('id')->first();

        if (! $cart && ($cartId = session('cart_id'))) {
            $cart = Cart::query()->whereKey($cartId)->where('store_id', $store->id)->where('status', CartStatus::Active)->first();
        }

        $cart ??= $this->create($store, $customer);
        session(['cart_id' => $cart->id]);

        return $cart;
    }

    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart): Cart {
            foreach ($guestCart->lines as $guestLine) {
                $existingLine = $customerCart->lines()->where('variant_id', $guestLine->variant_id)->first();

                if ($existingLine) {
                    $quantity = max($existingLine->quantity, $guestLine->quantity);
                    $this->setLineAmounts($existingLine, $existingLine->unit_price_amount, $quantity);
                    $existingLine->save();
                    $guestLine->delete();
                } else {
                    $guestLine->update(['cart_id' => $customerCart->id]);
                }
            }

            $guestCart->update(['status' => CartStatus::Abandoned]);
            $customerCart->increment('cart_version');
            session()->forget('cart_id');

            return $customerCart->refresh()->load('lines');
        });
    }

    public function assertExpectedVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $expectedVersion !== $cart->cart_version) {
            throw new CartVersionConflictException($cart->cart_version);
        }
    }

    private function setLineAmounts(CartLine $line, int $unitPrice, int $quantity): void
    {
        $subtotal = $unitPrice * $quantity;
        $line->fill(['quantity' => $quantity, 'unit_price_amount' => $unitPrice, 'line_subtotal_amount' => $subtotal, 'line_discount_amount' => 0, 'line_total_amount' => $subtotal]);
    }

    private function ensureAvailable(ProductVariant $variant, int $quantity): void
    {
        if (! $this->inventoryService->checkAvailability($variant->inventoryItem, $quantity)) {
            throw new InsufficientInventoryException($variant->inventoryItem->id, $quantity, $variant->inventoryItem->available);
        }
    }
}
