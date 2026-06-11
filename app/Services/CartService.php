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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Session key holding the guest cart id (spec 05 section 4.1).
     */
    public const string SESSION_KEY = 'cart_id';

    /**
     * Create a new active cart in the store's default currency at version 1.
     */
    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::query()->create([
            'store_id' => $store->getKey(),
            'customer_id' => $customer?->getKey(),
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    /**
     * Add a variant to the cart, incrementing an existing line for the same
     * variant instead of creating a duplicate.
     *
     * @throws ValidationException
     * @throws InsufficientInventoryException
     */
    public function addLine(Cart $cart, int $variantId, int $quantity): CartLine
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => __('Quantity must be at least 1.')]);
        }

        $variant = $this->resolvePurchasableVariant($cart, $variantId);

        return DB::transaction(function () use ($cart, $variant, $quantity): CartLine {
            $line = $cart->lines()->where('variant_id', $variant->getKey())->first();

            $newQuantity = ($line?->quantity ?? 0) + $quantity;

            $this->assertInventoryAllows($variant, $newQuantity);

            if ($line === null) {
                $line = new CartLine([
                    'cart_id' => $cart->getKey(),
                    'variant_id' => $variant->getKey(),
                    'quantity' => $quantity,
                    'unit_price_amount' => $variant->price_amount,
                    'line_discount_amount' => 0,
                ]);
            } else {
                $line->quantity = $newQuantity;
            }

            $line->recalculateAmounts();
            $line->save();

            $this->bumpVersion($cart);

            return $line;
        });
    }

    /**
     * Update a line's quantity, removing the line entirely when set to zero.
     *
     * @throws InsufficientInventoryException
     */
    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity): ?CartLine
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages(['quantity' => __('Quantity may not be negative.')]);
        }

        if ($quantity === 0) {
            $this->removeLine($cart, $lineId);

            return null;
        }

        $line = $cart->lines()->with('variant')->findOrFail($lineId);

        $this->assertInventoryAllows($line->variant, $quantity);

        return DB::transaction(function () use ($cart, $line, $quantity): CartLine {
            $line->quantity = $quantity;
            $line->recalculateAmounts();
            $line->save();

            $this->bumpVersion($cart);

            return $line;
        });
    }

    /**
     * Remove a line from the cart.
     */
    public function removeLine(Cart $cart, int $lineId): void
    {
        $line = $cart->lines()->findOrFail($lineId);

        DB::transaction(function () use ($cart, $line): void {
            $line->delete();

            $this->bumpVersion($cart);
        });
    }

    /**
     * Resolve the cart for the current session, preferring the customer's
     * active cart, then the session-bound guest cart, then a fresh cart.
     */
    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        if ($customer !== null) {
            $customerCart = Cart::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->where('customer_id', $customer->getKey())
                ->where('status', CartStatus::Active)
                ->latest('id')
                ->first();

            if ($customerCart !== null) {
                Session::put(self::SESSION_KEY, $customerCart->getKey());

                return $customerCart;
            }
        }

        $sessionCart = $this->findSessionCart($store);

        if ($sessionCart !== null) {
            if ($customer !== null && $sessionCart->customer_id === null) {
                $sessionCart->update(['customer_id' => $customer->getKey()]);
            }

            return $sessionCart;
        }

        $cart = $this->create($store, $customer);

        Session::put(self::SESSION_KEY, $cart->getKey());

        return $cart;
    }

    /**
     * The current cart without creating one: the customer's active cart when
     * logged in, otherwise the session-bound guest cart.
     */
    public function findFor(Store $store, ?Customer $customer = null): ?Cart
    {
        if ($customer !== null) {
            $customerCart = Cart::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->where('customer_id', $customer->getKey())
                ->where('status', CartStatus::Active)
                ->latest('id')
                ->first();

            if ($customerCart !== null) {
                return $customerCart;
            }
        }

        return $this->findSessionCart($store);
    }

    /**
     * The session-bound active cart for the store, if any.
     */
    public function findSessionCart(Store $store): ?Cart
    {
        $cartId = Session::get(self::SESSION_KEY);

        if ($cartId === null) {
            return null;
        }

        return Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('status', CartStatus::Active)
            ->find($cartId);
    }

    /**
     * Merge the guest cart's lines into the customer cart, summing quantities
     * for duplicate variants. The guest cart is marked abandoned.
     */
    public function mergeOnLogin(Cart $guestCart, Cart $customerCart): Cart
    {
        return DB::transaction(function () use ($guestCart, $customerCart): Cart {
            foreach ($guestCart->lines()->get() as $guestLine) {
                $existingLine = $customerCart->lines()
                    ->where('variant_id', $guestLine->variant_id)
                    ->first();

                if ($existingLine !== null) {
                    $existingLine->quantity += $guestLine->quantity;
                    $existingLine->recalculateAmounts();
                    $existingLine->save();
                    $guestLine->delete();
                } else {
                    $guestLine->update(['cart_id' => $customerCart->getKey()]);
                }
            }

            $guestCart->update(['status' => CartStatus::Abandoned]);

            $this->bumpVersion($customerCart);

            return $customerCart->refresh();
        });
    }

    /**
     * Guard for optimistic concurrency: API clients send the cart version
     * they last saw and receive a conflict when it has moved on.
     *
     * @throws CartVersionMismatchException
     */
    public function assertVersion(Cart $cart, int $expectedVersion): void
    {
        if ($cart->cart_version !== $expectedVersion) {
            throw CartVersionMismatchException::forVersions($expectedVersion, $cart->cart_version);
        }
    }

    /**
     * Resolve an active variant of an active product within the cart's store.
     *
     * @throws ValidationException
     */
    protected function resolvePurchasableVariant(Cart $cart, int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->with(['product' => fn ($query) => $query->withoutGlobalScopes(), 'inventoryItem'])
            ->find($variantId);

        if ($variant === null || $variant->product?->store_id !== $cart->store_id) {
            throw (new ModelNotFoundException)->setModel(ProductVariant::class, [$variantId]);
        }

        if ($variant->product->status !== ProductStatus::Active) {
            throw ValidationException::withMessages(['variant' => __('This product is not available.')]);
        }

        if ($variant->status !== VariantStatus::Active) {
            throw ValidationException::withMessages(['variant' => __('This variant is not available.')]);
        }

        return $variant;
    }

    /**
     * Enforce the deny inventory policy for the requested total quantity.
     *
     * @throws InsufficientInventoryException
     */
    protected function assertInventoryAllows(?ProductVariant $variant, int $quantity): void
    {
        $inventory = $variant?->inventoryItem;

        if ($inventory === null || $inventory->policy === InventoryPolicy::Continue) {
            return;
        }

        if ($inventory->availableQuantity() < $quantity) {
            throw InsufficientInventoryException::forQuantity($quantity, $inventory->availableQuantity());
        }
    }

    /**
     * Every cart mutation increments the optimistic concurrency version.
     */
    protected function bumpVersion(Cart $cart): void
    {
        $cart->increment('cart_version');
    }
}
