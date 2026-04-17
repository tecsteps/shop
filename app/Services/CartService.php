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
use RuntimeException;

class CartService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::query()->create([
            'store_id' => $store->getKey(),
            'customer_id' => $customer?->getKey(),
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active->value,
        ]);
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $sessionCartId = session('cart_id');

        if ($sessionCartId !== null) {
            $cart = Cart::query()->find($sessionCartId);

            if ($cart !== null && $cart->status === CartStatus::Active && (int) $cart->store_id === (int) $store->getKey()) {
                if ($customer !== null && $cart->customer_id === null) {
                    $cart->customer_id = $customer->getKey();
                    $cart->save();
                }

                return $cart;
            }
        }

        $cart = $this->create($store, $customer);
        session(['cart_id' => $cart->getKey()]);

        return $cart;
    }

    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        if ($quantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity, $expectedVersion): CartLine {
            $cart = $this->lockCart($cart);
            $this->assertVersion($cart, $expectedVersion);

            $variant = ProductVariant::query()->with('product')->findOrFail($variantId);

            if ($variant->product === null || (int) $variant->product->store_id !== (int) $cart->store_id) {
                throw new RuntimeException('Variant does not belong to this store.');
            }

            if ($variant->product->status !== ProductStatus::Active) {
                throw new RuntimeException('Product is not active.');
            }

            if ($variant->status !== VariantStatus::Active) {
                throw new RuntimeException('Variant is not active.');
            }

            $existing = CartLine::query()
                ->where('cart_id', $cart->getKey())
                ->where('variant_id', $variant->getKey())
                ->first();

            $targetQuantity = ($existing?->quantity ?? 0) + $quantity;

            if (! $this->inventory->checkAvailability($variant, $targetQuantity)) {
                throw new InsufficientInventoryException($variant->getKey(), $targetQuantity, 0);
            }

            if ($existing !== null) {
                $existing->quantity = $targetQuantity;
                $existing->unit_price_amount = (int) $variant->price_amount;
                $this->recalculateLine($existing);
                $existing->save();
                $line = $existing;
            } else {
                $price = (int) $variant->price_amount;
                $line = CartLine::query()->create([
                    'cart_id' => $cart->getKey(),
                    'variant_id' => $variant->getKey(),
                    'quantity' => $quantity,
                    'unit_price_amount' => $price,
                    'line_subtotal_amount' => $price * $quantity,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $price * $quantity,
                ]);
            }

            $this->touchVersion($cart);

            return $line->refresh();
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion): ?CartLine {
            $cart = $this->lockCart($cart);
            $this->assertVersion($cart, $expectedVersion);

            $line = CartLine::query()
                ->where('cart_id', $cart->getKey())
                ->where('id', $lineId)
                ->firstOrFail();

            if ($quantity <= 0) {
                $line->delete();
                $this->touchVersion($cart);

                return null;
            }

            $variant = ProductVariant::query()->findOrFail($line->variant_id);

            if (! $this->inventory->checkAvailability($variant, $quantity)) {
                throw new InsufficientInventoryException($variant->getKey(), $quantity, 0);
            }

            $line->quantity = $quantity;
            $line->unit_price_amount = (int) $variant->price_amount;
            $this->recalculateLine($line);
            $line->save();

            $this->touchVersion($cart);

            return $line->refresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion): void {
            $cart = $this->lockCart($cart);
            $this->assertVersion($cart, $expectedVersion);

            CartLine::query()
                ->where('cart_id', $cart->getKey())
                ->where('id', $lineId)
                ->delete();

            $this->touchVersion($cart);
        });
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        if ((int) $guest->store_id !== (int) $customer->store_id) {
            throw new RuntimeException('Cannot merge carts across stores.');
        }

        return DB::transaction(function () use ($guest, $customer): Cart {
            foreach ($guest->lines()->get() as $guestLine) {
                $target = CartLine::query()
                    ->where('cart_id', $customer->getKey())
                    ->where('variant_id', $guestLine->variant_id)
                    ->first();

                if ($target === null) {
                    $guestLine->cart_id = $customer->getKey();
                    $guestLine->save();
                } else {
                    $target->quantity = max($target->quantity, $guestLine->quantity);
                    $this->recalculateLine($target);
                    $target->save();
                    $guestLine->delete();
                }
            }

            $guest->status = CartStatus::Abandoned;
            $guest->save();

            $this->touchVersion($customer->refresh());

            return $customer->refresh();
        });
    }

    public function recalculate(Cart $cart): void
    {
        foreach ($cart->lines as $line) {
            $this->recalculateLine($line);
            $line->save();
        }
    }

    protected function recalculateLine(CartLine $line): void
    {
        $line->line_subtotal_amount = $line->unit_price_amount * $line->quantity;
        $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
    }

    protected function lockCart(Cart $cart): Cart
    {
        $locked = Cart::query()
            ->where('id', $cart->getKey())
            ->lockForUpdate()
            ->first();

        return $locked ?? $cart;
    }

    protected function assertVersion(Cart $cart, ?int $expected): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $cart->cart_version !== $expected) {
            throw new CartVersionConflictException($expected, (int) $cart->cart_version);
        }
    }

    protected function touchVersion(Cart $cart): Cart
    {
        $cart->cart_version = (int) $cart->cart_version + 1;
        $cart->save();

        return $cart;
    }
}
