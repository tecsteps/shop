<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
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
    public const SESSION_KEY = 'cart_id';

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency ?? 'USD',
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            $variant = $this->loadVariantForCart($cart, $variantId);

            $existing = $cart->lines()->where('variant_id', $variantId)->lockForUpdate()->first();

            if ($existing) {
                $newQuantity = $existing->quantity + $quantity;
                $this->guardInventory($variant, $newQuantity);
                $this->updateLineAmounts($existing, $newQuantity, $variant->price_amount);
                $existing->save();
                $line = $existing;
            } else {
                $this->guardInventory($variant, $quantity);
                $line = new CartLine([
                    'cart_id' => $cart->id,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                ]);
                $this->updateLineAmounts($line, $quantity, $variant->price_amount);
                $line->save();
            }

            $this->bumpVersion($cart);

            return $line;
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion): CartLine {
            $this->assertVersion($cart, $expectedVersion);

            $line = $cart->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            $variant = $this->loadVariantForCart($cart, $line->variant_id);
            $this->guardInventory($variant, $quantity);

            $this->updateLineAmounts($line, $quantity, $variant->price_amount);
            $line->save();

            $this->bumpVersion($cart);

            return $line;
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion): void {
            $this->assertVersion($cart, $expectedVersion);

            $line = $cart->lines()->whereKey($lineId)->firstOrFail();
            $line->delete();

            $this->bumpVersion($cart);
        });
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $session = session();
        $cartId = $session->get(self::SESSION_KEY);

        if ($cartId) {
            $cart = Cart::query()
                ->where('store_id', $store->id)
                ->where('status', CartStatus::Active)
                ->find($cartId);

            if ($cart) {
                if ($customer && ! $cart->customer_id) {
                    $cart->customer_id = $customer->id;
                    $cart->save();
                }

                return $cart;
            }
        }

        $cart = $this->create($store, $customer);
        $session->put(self::SESSION_KEY, $cart->id);

        return $cart;
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        if ($guest->id === $customer->id) {
            return $customer;
        }

        return DB::transaction(function () use ($guest, $customer): Cart {
            foreach ($guest->lines as $guestLine) {
                $existing = $customer->lines()->where('variant_id', $guestLine->variant_id)->first();
                $variant = $this->loadVariantForCart($customer, $guestLine->variant_id);

                if ($existing) {
                    $quantity = max($existing->quantity, $guestLine->quantity);
                    $this->updateLineAmounts($existing, $quantity, $variant->price_amount);
                    $existing->save();
                } else {
                    $line = new CartLine([
                        'cart_id' => $customer->id,
                        'variant_id' => $guestLine->variant_id,
                        'quantity' => $guestLine->quantity,
                    ]);
                    $this->updateLineAmounts($line, $guestLine->quantity, $variant->price_amount);
                    $line->save();
                }
            }

            $guest->status = CartStatus::Abandoned;
            $guest->save();

            $customer->setRelation('lines', $customer->lines()->get());
            $this->bumpVersion($customer);

            return $customer;
        });
    }

    public function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion === null) {
            return;
        }

        $cart->refresh();
        if ($cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
        }
    }

    protected function bumpVersion(Cart $cart): void
    {
        $cart->cart_version = $cart->cart_version + 1;
        $cart->save();
    }

    protected function updateLineAmounts(CartLine $line, int $quantity, int $unitPrice): void
    {
        $line->quantity = $quantity;
        $line->unit_price_amount = $unitPrice;
        $line->line_subtotal_amount = $unitPrice * $quantity;
        $line->line_discount_amount = 0;
        $line->line_total_amount = $line->line_subtotal_amount;
    }

    protected function loadVariantForCart(Cart $cart, int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->with(['product', 'inventoryItem'])
            ->whereKey($variantId)
            ->first();

        if (! $variant) {
            throw ValidationException::withMessages(['variant_id' => 'Variant not found.']);
        }

        if ($variant->status !== VariantStatus::Active) {
            throw ValidationException::withMessages(['variant_id' => 'Variant is not available.']);
        }

        if ($variant->product?->store_id !== $cart->store_id) {
            throw ValidationException::withMessages(['variant_id' => 'Variant does not belong to this store.']);
        }

        if ($variant->product?->status !== ProductStatus::Active) {
            throw ValidationException::withMessages(['variant_id' => 'Product is not available.']);
        }

        return $variant;
    }

    protected function guardInventory(ProductVariant $variant, int $quantity): void
    {
        $item = $variant->inventoryItem;

        if (! $item) {
            return;
        }

        if ($item->policy !== InventoryPolicy::Deny) {
            return;
        }

        if ($item->available() < $quantity) {
            throw new InsufficientInventoryException(
                "Requested {$quantity} exceeds available stock of {$item->available()} for variant {$variant->id}."
            );
        }
    }
}
