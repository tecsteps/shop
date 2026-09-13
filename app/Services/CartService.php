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

/**
 * Cart lifecycle and line management per spec 05 section 4.
 */
class CartService
{
    public function __construct(protected InventoryService $inventory) {}

    public function create(Store $store, ?Customer $customer = null, ?string $currency = null): Cart
    {
        return Cart::query()->create([
            'store_id' => $store->getKey(),
            'customer_id' => $customer?->getKey(),
            'currency' => $currency ?? $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $cartId = session()->get('cart_id');

        if ($cartId !== null) {
            $cart = Cart::query()
                ->where('store_id', $store->getKey())
                ->whereKey($cartId)
                ->where('status', CartStatus::Active)
                ->first();

            if ($cart !== null) {
                return $cart;
            }
        }

        if ($customer !== null) {
            $customerCart = Cart::query()
                ->where('store_id', $store->getKey())
                ->where('customer_id', $customer->getKey())
                ->where('status', CartStatus::Active)
                ->first();

            if ($customerCart !== null) {
                return $customerCart;
            }
        }

        $cart = $this->create($store, $customer);

        if ($customer === null) {
            session()->put('cart_id', $cart->getKey());
        }

        return $cart;
    }

    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        $this->assertVersion($cart, $expectedVersion);

        if ($quantity < 1 || $quantity > 9999) {
            throw ValidationException::withMessages([
                'quantity' => ['The quantity must be between 1 and 9999.'],
            ]);
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            $variant = $this->findActiveVariant($cart, $variantId);

            $line = $cart->cartLines()->where('variant_id', $variant->getKey())->first();
            $newQuantity = ($line?->quantity ?? 0) + $quantity;

            $this->assertInventoryAvailable($variant, $newQuantity);

            if ($line === null) {
                $line = $cart->cartLines()->create([
                    'variant_id' => $variant->getKey(),
                    'quantity' => $newQuantity,
                    'unit_price_amount' => $variant->price_amount,
                    'line_subtotal_amount' => $variant->price_amount * $newQuantity,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $variant->price_amount * $newQuantity,
                ]);
            } else {
                $line->quantity = $newQuantity;
                $line->line_subtotal_amount = $line->unit_price_amount * $newQuantity;
                $line->line_discount_amount = 0;
                $line->line_total_amount = $line->line_subtotal_amount;
                $line->save();
            }

            $cart->increment('cart_version');

            return $line->refresh();
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        $this->assertVersion($cart, $expectedVersion);

        if ($quantity < 0 || $quantity > 9999) {
            throw ValidationException::withMessages([
                'quantity' => ['The quantity must be between 0 and 9999.'],
            ]);
        }

        if ($quantity === 0) {
            $this->removeLine($cart, $lineId);

            return null;
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity): CartLine {
            $line = $cart->cartLines()->whereKey($lineId)->firstOrFail();

            $this->assertInventoryAvailable($line->variant, $quantity);

            $line->quantity = $quantity;
            $line->line_subtotal_amount = $line->unit_price_amount * $quantity;
            $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
            $line->save();

            $cart->increment('cart_version');

            return $line->refresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        $this->assertVersion($cart, $expectedVersion);

        DB::transaction(function () use ($cart, $lineId): void {
            $line = $cart->cartLines()->whereKey($lineId)->firstOrFail();
            $line->delete();

            $cart->increment('cart_version');
        });
    }

    /**
     * Merge a guest cart into the customer's cart on login per spec 05 4.1.
     * Duplicate variants keep the higher quantity (MAX).
     */
    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart): Cart {
            foreach ($guestCart->cartLines as $line) {
                $existing = $customerCart->cartLines()->where('variant_id', $line->variant_id)->first();

                if ($existing === null) {
                    $line->cart_id = $customerCart->getKey();
                    $line->save();

                    continue;
                }

                $existing->quantity = max($existing->quantity, $line->quantity);
                $existing->line_subtotal_amount = $existing->unit_price_amount * $existing->quantity;
                $existing->line_discount_amount = 0;
                $existing->line_total_amount = $existing->line_subtotal_amount;
                $existing->save();
            }

            $guestCart->status = CartStatus::Abandoned;
            $guestCart->save();

            $customerCart->increment('cart_version');

            return $customerCart->refresh();
        });
    }

    public function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException($cart->cart_version);
        }
    }

    protected function findActiveVariant(Cart $cart, int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->whereHas('product', fn ($query) => $query->where('store_id', $cart->store_id))
            ->first();

        if ($variant === null) {
            throw ValidationException::withMessages([
                'variant_id' => ['The selected variant is invalid.'],
            ]);
        }

        if ($variant->product->status !== ProductStatus::Active) {
            throw ValidationException::withMessages([
                'variant_id' => ['The selected product is not available.'],
            ]);
        }

        if ($variant->status !== VariantStatus::Active) {
            throw ValidationException::withMessages([
                'variant_id' => ['The selected variant is not available.'],
            ]);
        }

        return $variant;
    }

    protected function assertInventoryAvailable(ProductVariant $variant, int $quantity): void
    {
        $item = $variant->inventoryItem;

        if ($item === null) {
            return;
        }

        if ($item->policy === InventoryPolicy::Continue) {
            return;
        }

        if (! $this->inventory->checkAvailability($item, $quantity)) {
            throw new InsufficientInventoryException(
                "Insufficient inventory: requested {$quantity}, available {$this->inventory->available($item)}."
            );
        }
    }
}
