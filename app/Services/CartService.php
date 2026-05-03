<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InvalidCartMutationException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public const SESSION_KEY = 'cart_id';

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly DiscountService $discounts,
    ) {}

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

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $guestCart = $this->cartFromSession($store);

        if ($customer !== null) {
            $customerCart = Cart::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->latest()
                ->first();

            if ($guestCart !== null && $customerCart !== null && $guestCart->isNot($customerCart)) {
                $merged = $this->mergeOnLogin($guestCart, $customerCart);
                session()->forget(self::SESSION_KEY);

                return $merged;
            }

            if ($customerCart !== null) {
                session()->forget(self::SESSION_KEY);

                return $customerCart;
            }
        }

        if ($guestCart !== null) {
            return $guestCart;
        }

        $cart = $this->create($store, $customer);

        if ($customer === null) {
            session()->put(self::SESSION_KEY, $cart->id);
        }

        return $cart;
    }

    public function findForStore(Store $store, int $cartId): Cart
    {
        return Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($cartId)
            ->firstOrFail();
    }

    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        $this->guardPositiveQuantity($quantity);

        return DB::transaction(function () use ($cart, $variantId, $quantity, $expectedVersion): CartLine {
            $lockedCart = $this->lockCart($cart);
            $this->assertVersion($lockedCart, $expectedVersion);
            $this->guardActiveCart($lockedCart);

            $variant = $this->validVariantForCart($lockedCart, $variantId);
            $line = CartLine::query()
                ->where('cart_id', $lockedCart->id)
                ->where('variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = ($line?->quantity ?? 0) + $quantity;
            $this->guardAvailable($variant, $newQuantity);

            $line ??= new CartLine([
                'cart_id' => $lockedCart->id,
                'variant_id' => $variant->id,
            ]);

            $this->fillLineAmounts($line, $variant->price_amount, $newQuantity);
            $line->save();
            $this->repriceStoredDiscount($lockedCart);
            $this->incrementVersion($lockedCart);

            return $line->refresh()->load('variant.product', 'variant.inventoryItem');
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        if ($quantity < 1) {
            $this->removeLine($cart, $lineId, $expectedVersion);

            return null;
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion): CartLine {
            $lockedCart = $this->lockCart($cart);
            $this->assertVersion($lockedCart, $expectedVersion);
            $this->guardActiveCart($lockedCart);

            $line = CartLine::query()
                ->where('cart_id', $lockedCart->id)
                ->whereKey($lineId)
                ->lockForUpdate()
                ->firstOrFail();

            $variant = $this->validVariantForCart($lockedCart, $line->variant_id);
            $this->guardAvailable($variant, $quantity);
            $this->fillLineAmounts($line, $variant->price_amount, $quantity);
            $line->save();
            $this->repriceStoredDiscount($lockedCart);
            $this->incrementVersion($lockedCart);

            return $line->refresh()->load('variant.product', 'variant.inventoryItem');
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion): void {
            $lockedCart = $this->lockCart($cart);
            $this->assertVersion($lockedCart, $expectedVersion);
            $this->guardActiveCart($lockedCart);

            CartLine::query()
                ->where('cart_id', $lockedCart->id)
                ->whereKey($lineId)
                ->lockForUpdate()
                ->firstOrFail()
                ->delete();

            $this->repriceStoredDiscount($lockedCart);
            $this->incrementVersion($lockedCart);
        });
    }

    public function applyDiscount(Cart $cart, string $code): Cart
    {
        return DB::transaction(function () use ($cart, $code): Cart {
            $lockedCart = $this->lockCart($cart);
            $this->guardActiveCart($lockedCart);
            $lockedCart->load('store', 'lines.variant.product.collections');

            $discount = $this->discounts->validate($code, $lockedCart->store, $lockedCart);

            $lockedCart->forceFill([
                'discount_code' => Str::upper((string) $discount->code),
            ])->save();

            $this->applyValidatedDiscount($lockedCart, $discount);
            $this->incrementVersion($lockedCart);

            return $this->loadForDisplay($lockedCart);
        });
    }

    public function removeDiscount(Cart $cart): Cart
    {
        return DB::transaction(function () use ($cart): Cart {
            $lockedCart = $this->lockCart($cart);
            $this->guardActiveCart($lockedCart);
            $lockedCart->load('lines');

            if ($lockedCart->discount_code !== null) {
                $lockedCart->forceFill(['discount_code' => null])->save();
                $this->applyLineDiscounts($lockedCart->lines, []);
                $this->incrementVersion($lockedCart);
            }

            return $this->loadForDisplay($lockedCart);
        });
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        return DB::transaction(function () use ($guest, $customer): Cart {
            $guest = $this->lockCart($guest)->load('lines');
            $customer = $this->lockCart($customer)->load('lines');

            foreach ($guest->lines as $guestLine) {
                $customerLine = $customer->lines->firstWhere('variant_id', $guestLine->variant_id);

                if ($customerLine !== null) {
                    $quantity = max($customerLine->quantity, $guestLine->quantity);
                    $variant = $this->validVariantForCart($customer, $customerLine->variant_id);
                    $this->guardAvailable($variant, $quantity);
                    $this->fillLineAmounts($customerLine, $variant->price_amount, $quantity);
                    $customerLine->save();
                    $guestLine->delete();

                    continue;
                }

                $guestLine->forceFill(['cart_id' => $customer->id])->save();
            }

            if ($customer->discount_code === null && $guest->discount_code !== null) {
                $customer->forceFill(['discount_code' => $guest->discount_code])->save();
            }

            $this->repriceStoredDiscount($customer);
            $guest->forceFill(['status' => CartStatus::Abandoned])->save();
            $this->incrementVersion($customer);

            return $customer->refresh()->load('lines.variant.product', 'lines.variant.inventoryItem');
        });
    }

    public function loadForDisplay(Cart $cart): Cart
    {
        return $cart->refresh()->load(
            'lines.variant.product.media',
            'lines.variant.optionValues.option',
            'lines.variant.inventoryItem',
        );
    }

    public function findActiveForSession(Store $store): ?Cart
    {
        return $this->cartFromSession($store);
    }

    public function forgetSessionCart(?Cart $cart = null): void
    {
        $cartId = session()->get(self::SESSION_KEY);

        if (! $cartId) {
            return;
        }

        if ($cart === null || (int) $cart->getKey() === (int) $cartId) {
            session()->forget(self::SESSION_KEY);
        }
    }

    private function cartFromSession(Store $store): ?Cart
    {
        $cartId = session()->get(self::SESSION_KEY);

        if (! $cartId) {
            return null;
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active)
            ->whereKey($cartId)
            ->first();

        if (! $cart instanceof Cart) {
            session()->forget(self::SESSION_KEY);

            return null;
        }

        return $cart;
    }

    private function lockCart(Cart $cart): Cart
    {
        return Cart::withoutGlobalScopes()
            ->whereKey($cart->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $cart->cart_version !== $expectedVersion) {
            throw new CartVersionConflictException($this->loadForDisplay($cart));
        }
    }

    private function guardActiveCart(Cart $cart): void
    {
        if ($cart->status !== CartStatus::Active) {
            throw new InvalidCartMutationException('Only active carts may be changed.');
        }
    }

    private function validVariantForCart(Cart $cart, int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->with([
                'product' => fn ($query) => $query->withoutGlobalScopes(),
                'inventoryItem' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->whereKey($variantId)
            ->firstOrFail();

        if ($variant->product->store_id !== $cart->store_id) {
            throw new InvalidCartMutationException('The selected variant does not belong to this store.');
        }

        if ($variant->product->status !== ProductStatus::Active || $variant->status !== VariantStatus::Active) {
            throw new InvalidCartMutationException('The selected variant is not available.');
        }

        if ($variant->inventoryItem === null) {
            throw new InvalidCartMutationException('The selected variant has no inventory record.');
        }

        return $variant;
    }

    private function guardAvailable(ProductVariant $variant, int $quantity): void
    {
        if (! $this->inventory->checkAvailability($variant->inventoryItem, $quantity)) {
            throw new InvalidCartMutationException('The selected variant is out of stock.');
        }
    }

    private function fillLineAmounts(CartLine $line, int $unitPrice, int $quantity): void
    {
        $subtotal = $unitPrice * $quantity;

        $line->forceFill([
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ]);
    }

    private function repriceStoredDiscount(Cart $cart): void
    {
        if ($cart->discount_code === null) {
            return;
        }

        $cart->load('store', 'lines.variant.product.collections');

        if ($cart->lines->isEmpty()) {
            $cart->forceFill(['discount_code' => null])->save();

            return;
        }

        try {
            $discount = $this->discounts->validate($cart->discount_code, $cart->store, $cart);
            $this->applyValidatedDiscount($cart, $discount);
        } catch (InvalidDiscountException) {
            $cart->forceFill(['discount_code' => null])->save();
            $this->applyLineDiscounts($cart->lines, []);
        }
    }

    private function applyValidatedDiscount(Cart $cart, Discount $discount): void
    {
        $cart->load('lines.variant.product.collections');

        $discountResult = $this->discounts->calculate(
            $discount,
            (int) $cart->lines->sum('line_subtotal_amount'),
            $cart->lines,
        );

        $this->applyLineDiscounts($cart->lines, $discountResult->allocations);
    }

    /**
     * @param  iterable<CartLine>  $lines
     * @param  array<int, int>  $allocations
     */
    private function applyLineDiscounts(iterable $lines, array $allocations): void
    {
        foreach ($lines as $line) {
            $discount = min($allocations[$line->id] ?? 0, $line->line_subtotal_amount);

            $line->forceFill([
                'line_discount_amount' => $discount,
                'line_total_amount' => $line->line_subtotal_amount - $discount,
            ])->save();
        }
    }

    private function incrementVersion(Cart $cart): void
    {
        $cart->increment('cart_version');
    }

    private function guardPositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidCartMutationException('Quantity must be greater than zero.');
        }
    }
}
