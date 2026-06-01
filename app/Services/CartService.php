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
use RuntimeException;

/**
 * Owns the cart aggregate: creation, line management, session binding, and the
 * guest-to-customer merge on login.
 *
 * Every mutation increments `cart_version` (optimistic concurrency). API callers
 * may pass an `expectedVersion`; a mismatch raises
 * {@see CartVersionMismatchException} which surfaces as HTTP 409. All line
 * amounts are integers in minor units (cents).
 */
class CartService
{
    /**
     * The session key holding the current guest cart id.
     */
    public const SESSION_KEY = 'cart_id';

    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * Create an empty cart for a store (optionally owned by a customer).
     */
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active->value,
        ]);
    }

    /**
     * Return the session cart for a store, creating one if absent.
     *
     * When a customer is supplied their existing active cart is preferred, and
     * the session is updated to point at it.
     */
    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        if ($customer !== null) {
            $cart = Cart::query()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active->value)
                ->latest('id')
                ->first() ?? $this->create($store, $customer);

            session()->put(self::SESSION_KEY, $cart->id);

            return $cart;
        }

        $cartId = session(self::SESSION_KEY);

        if ($cartId !== null) {
            $cart = Cart::query()
                ->where('store_id', $store->id)
                ->where('status', CartStatus::Active->value)
                ->find($cartId);

            if ($cart !== null) {
                return $cart;
            }
        }

        $cart = $this->create($store);
        session()->put(self::SESSION_KEY, $cart->id);

        return $cart;
    }

    /**
     * Add a variant to the cart (or increment an existing line).
     *
     * Validates the product and variant are active and, under a `deny` inventory
     * policy, that enough stock is available.
     *
     * @throws InsufficientInventoryException
     * @throws CartVersionMismatchException
     */
    public function addLine(Cart $cart, int $variantId, int $quantity = 1, ?int $expectedVersion = null): CartLine
    {
        $this->assertVersion($cart, $expectedVersion);

        if ($quantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartLine {
            $variant = $this->resolveVariant($cart, $variantId);

            $existing = $cart->lines()->where('variant_id', $variantId)->first();
            $newQuantity = ($existing?->quantity ?? 0) + $quantity;

            $this->assertInventory($variant, $newQuantity);

            $line = $existing ?? new CartLine([
                'cart_id' => $cart->id,
                'variant_id' => $variantId,
            ]);

            $line->fill($this->lineAmounts($variant->price_amount, $newQuantity, $line->line_discount_amount ?? 0));
            $line->save();

            $this->bumpVersion($cart);

            return $line;
        });
    }

    /**
     * Update a line's quantity, recalculating amounts. A quantity of zero (or
     * less) removes the line instead.
     *
     * @throws CartVersionMismatchException
     */
    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        $this->assertVersion($cart, $expectedVersion);

        if ($quantity <= 0) {
            $this->removeLine($cart, $lineId);

            return null;
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity): CartLine {
            $line = $cart->lines()->findOrFail($lineId);
            $variant = $this->resolveVariant($cart, $line->variant_id);

            $this->assertInventory($variant, $quantity);

            $line->fill($this->lineAmounts($variant->price_amount, $quantity, $line->line_discount_amount));
            $line->save();

            $this->bumpVersion($cart);

            return $line;
        });
    }

    /**
     * Remove a line from the cart.
     *
     * @throws CartVersionMismatchException
     */
    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        $this->assertVersion($cart, $expectedVersion);

        DB::transaction(function () use ($cart, $lineId): void {
            $cart->lines()->where('id', $lineId)->delete();
            $this->bumpVersion($cart);
        });
    }

    /**
     * Merge a guest cart into a customer cart on login.
     *
     * For each guest line, an existing matching customer line takes the higher
     * of the two quantities; otherwise the line is moved across. The guest cart
     * is then marked abandoned and all customer-cart amounts recalculated.
     */
    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart): Cart {
            foreach ($guestCart->lines as $guestLine) {
                $existing = $customerCart->lines()->where('variant_id', $guestLine->variant_id)->first();

                if ($existing !== null) {
                    $quantity = max($existing->quantity, $guestLine->quantity);
                    $existing->fill($this->lineAmounts($existing->unit_price_amount, $quantity, 0));
                    $existing->save();
                } else {
                    $customerCart->lines()->create([
                        'variant_id' => $guestLine->variant_id,
                        'quantity' => $guestLine->quantity,
                        'unit_price_amount' => $guestLine->unit_price_amount,
                        'line_subtotal_amount' => $guestLine->unit_price_amount * $guestLine->quantity,
                        'line_discount_amount' => 0,
                        'line_total_amount' => $guestLine->unit_price_amount * $guestLine->quantity,
                    ]);
                }
            }

            $guestCart->update(['status' => CartStatus::Abandoned->value]);

            $this->bumpVersion($customerCart);
            session()->put(self::SESSION_KEY, $customerCart->id);

            return $customerCart->fresh('lines');
        });
    }

    /**
     * Resolve a variant within the cart's store, verifying it is purchasable.
     */
    private function resolveVariant(Cart $cart, int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->with(['product', 'inventoryItem'])
            ->whereHas('product', fn ($query) => $query->where('store_id', $cart->store_id))
            ->find($variantId);

        if ($variant === null) {
            throw new RuntimeException("Variant {$variantId} does not belong to this store.");
        }

        if ($variant->product->status !== ProductStatus::Active) {
            throw new RuntimeException('This product is not available for purchase.');
        }

        if ($variant->status !== VariantStatus::Active) {
            throw new RuntimeException('This variant is not available for purchase.');
        }

        return $variant;
    }

    /**
     * @throws InsufficientInventoryException
     */
    private function assertInventory(ProductVariant $variant, int $quantity): void
    {
        $item = $variant->inventoryItem;

        if ($item !== null
            && $item->policy === InventoryPolicy::Deny
            && ! $this->inventory->checkAvailability($item, $quantity)) {
            throw new InsufficientInventoryException(
                "Insufficient inventory for variant {$variant->id}: requested {$quantity}, available {$item->available()}.",
            );
        }
    }

    /**
     * @return array{quantity: int, unit_price_amount: int, line_subtotal_amount: int, line_discount_amount: int, line_total_amount: int}
     */
    private function lineAmounts(int $unitPrice, int $quantity, int $discount): array
    {
        $subtotal = $unitPrice * $quantity;

        return [
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => $discount,
            'line_total_amount' => $subtotal - $discount,
        ];
    }

    private function bumpVersion(Cart $cart): void
    {
        $cart->increment('cart_version');
        $cart->touch();
    }

    /**
     * @throws CartVersionMismatchException
     */
    private function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $expectedVersion !== $cart->cart_version) {
            throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
        }
    }
}
