<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
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
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            $variant = ProductVariant::query()
                ->with(['product', 'inventoryItem'])
                ->findOrFail($variantId);

            if ($variant->product->store_id !== $cart->store_id
                || $variant->product->status !== ProductStatus::Active
                || $variant->status !== VariantStatus::Active) {
                throw ValidationException::withMessages(['variant' => 'This variant is not available.']);
            }

            $line = $cart->lines()->where('variant_id', $variant->id)->first();
            $newQuantity = ($line?->quantity ?? 0) + $quantity;

            if (! $variant->inventoryItem || ! $this->inventoryService->checkAvailability($variant->inventoryItem, $newQuantity)) {
                throw new InsufficientInventoryException;
            }

            $amounts = $this->lineAmounts($variant->price_amount, $newQuantity);
            $line = $cart->lines()->updateOrCreate(['variant_id' => $variant->id], $amounts);
            $cart->increment('cart_version');

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): CartLine
    {
        if ($quantity === 0) {
            $line = $cart->lines()->findOrFail($lineId);
            $this->removeLine($cart, $lineId);

            return $line;
        }

        if ($quantity < 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity cannot be negative.']);
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity): CartLine {
            $line = $cart->lines()->with('variant.inventoryItem')->findOrFail($lineId);

            if (! $line->variant->inventoryItem
                || ! $this->inventoryService->checkAvailability($line->variant->inventoryItem, $quantity)) {
                throw new InsufficientInventoryException;
            }

            $line->update($this->lineAmounts($line->variant->price_amount, $quantity));
            $cart->increment('cart_version');

            return $line;
        });
    }

    public function removeLine(Cart $cart, int $lineId): void
    {
        DB::transaction(function () use ($cart, $lineId): void {
            $cart->lines()->findOrFail($lineId)->delete();
            $cart->increment('cart_version');
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $cart = session()->has('cart_id')
            ? Cart::query()->whereKey(session('cart_id'))->where('status', CartStatus::Active)->first()
            : null;

        if (! $cart) {
            $cart = $this->create($store, $customer);
            session(['cart_id' => $cart->id]);
        }

        return $cart;
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        return DB::transaction(function () use ($guest, $customer): Cart {
            foreach ($guest->lines()->get() as $guestLine) {
                $customerLine = $customer->lines()->where('variant_id', $guestLine->variant_id)->first();
                $quantity = max($guestLine->quantity, $customerLine?->quantity ?? 0);

                if ($customerLine) {
                    $customerLine->update($this->lineAmounts($guestLine->unit_price_amount, $quantity));
                    $guestLine->delete();
                } else {
                    $guestLine->update(['cart_id' => $customer->id]);
                }
            }

            $guest->update(['status' => CartStatus::Abandoned]);
            $customer->increment('cart_version');
            session()->forget('cart_id');

            return $customer->fresh('lines');
        });
    }

    /** @return array{quantity: int, unit_price_amount: int, line_subtotal_amount: int, line_discount_amount: int, line_total_amount: int} */
    private function lineAmounts(int $unitPrice, int $quantity): array
    {
        $subtotal = $unitPrice * $quantity;

        return [
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ];
    }
}
