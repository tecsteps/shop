<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

/**
 * Cart lifecycle and line operations (spec 05 §4). Every mutation
 * increments cart_version by 1 for optimistic concurrency.
 */
class CartService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Create a cart for the store with its default currency, version 1.
     */
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

    /**
     * Add a variant to the cart. Existing lines for the variant are
     * incremented instead of duplicated (spec 05 §4.2).
     *
     * @throws ValidationException invalid variant / inactive product
     * @throws InsufficientInventoryException policy "deny" and out of stock
     */
    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->whereHas('product', fn ($query) => $query->where('store_id', $cart->store_id))
            ->with(['product', 'inventoryItem'])
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

        $line = $cart->lines()->where('variant_id', $variantId)->first();
        $newQuantity = ($line?->quantity ?? 0) + $quantity;

        // The merged quantity is what will be reserved at checkout, so the
        // inventory policy is checked against it.
        $item = $variant->inventoryItem;

        if ($item !== null && ! $this->inventory->checkAvailability($item, $newQuantity)) {
            throw InsufficientInventoryException::forReservation($item, $newQuantity);
        }

        if ($line !== null) {
            $line->quantity = $newQuantity;
            $line->recalculate();
        } else {
            $line = new CartLine([
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'unit_price_amount' => $variant->price_amount,
                'line_discount_amount' => 0,
            ]);
            $line->cart()->associate($cart);
            $line->recalculate();
        }

        $this->touchVersion($cart);

        return $line;
    }

    /**
     * Update a line's quantity. Setting 0 removes the line.
     *
     * @throws InsufficientInventoryException policy "deny" and out of stock
     */
    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): CartLine
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['The quantity must not be negative.'],
            ]);
        }

        $line = $cart->lines()->findOrFail($lineId);

        if ($quantity === 0) {
            $line->delete();
            $this->touchVersion($cart);

            return $line;
        }

        $item = $line->variant?->inventoryItem;

        if ($item !== null && ! $this->inventory->checkAvailability($item, $quantity)) {
            throw InsufficientInventoryException::forReservation($item, $quantity);
        }

        $line->quantity = $quantity;
        $line->recalculate();

        $this->touchVersion($cart);

        return $line;
    }

    /**
     * Remove a line from the cart.
     */
    public function removeLine(Cart $cart, int $lineId): void
    {
        $cart->lines()->findOrFail($lineId)->delete();

        $this->touchVersion($cart);
    }

    /**
     * The active cart bound to the current session, or null when the
     * visitor has no cart yet (does not create one).
     */
    public function findForSession(Store $store): ?Cart
    {
        $cartId = session('cart_id');

        if ($cartId === null) {
            return null;
        }

        return Cart::query()
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active)
            ->find($cartId);
    }

    /**
     * The active cart for the current session, creating and binding one on
     * first use (spec 05 §4.1 guest identification).
     */
    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $cart = $this->findForSession($store);

        if ($cart === null) {
            $cart = $this->create($store, $customer);
            session(['cart_id' => $cart->id]);
        } elseif ($customer !== null && $cart->customer_id === null) {
            $cart->update(['customer_id' => $customer->id]);
        }

        return $cart;
    }

    /**
     * Merge a guest cart into a customer cart on login: duplicate variants
     * keep the combined quantity, the guest cart is abandoned and the
     * session key is cleared (spec 05 §4.1; the spec 09 test tables require
     * summed quantities, which overrides the MAX() in the §4.1 pseudocode).
     */
    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        $guest->loadMissing('lines');
        $customer->loadMissing('lines');

        foreach ($guest->lines as $line) {
            $existing = $customer->findLineByVariant($line->variant_id);

            if ($existing !== null) {
                $existing->quantity += $line->quantity;
                $existing->recalculate();
            } else {
                $line->cart()->associate($customer);
                $line->save();
            }
        }

        $guest->update(['status' => CartStatus::Abandoned]);

        $customer->unsetRelation('lines');
        $customer->load('lines');
        $customer->recalculateLines();

        $this->touchVersion($customer);

        session()->forget('cart_id');

        return $customer->refresh();
    }

    /**
     * Verify the client's expected version against the current version.
     *
     * @throws CartVersionMismatchException
     */
    public function assertVersion(Cart $cart, int $expectedVersion): void
    {
        if ($cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException($cart);
        }
    }

    /**
     * Increment the cart version and keep the in-memory model in sync.
     */
    private function touchVersion(Cart $cart): void
    {
        $cart->increment('cart_version');
        $cart->refresh();
    }
}
