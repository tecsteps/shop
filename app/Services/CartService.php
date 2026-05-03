<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InvalidCartOperationException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {}

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            'customer_id' => $customer?->getKey(),
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        $sessionCartId = session('cart_id');

        if ($customer instanceof Customer) {
            $customerCart = Cart::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->where('customer_id', $customer->getKey())
                ->where('status', CartStatus::Active)
                ->latest('id')
                ->first() ?? $this->create($store, $customer);

            if ($sessionCartId) {
                $guestCart = Cart::withoutGlobalScopes()
                    ->where('store_id', $store->getKey())
                    ->whereNull('customer_id')
                    ->where('status', CartStatus::Active)
                    ->find($sessionCartId);

                if ($guestCart instanceof Cart && $guestCart->isNot($customerCart)) {
                    $customerCart = $this->mergeOnLogin($guestCart, $customerCart);
                }

                session()->forget('cart_id');
            }

            return $customerCart->refresh();
        }

        if ($sessionCartId) {
            $cart = Cart::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->whereNull('customer_id')
                ->where('status', CartStatus::Active)
                ->find($sessionCartId);

            if ($cart instanceof Cart) {
                return $cart;
            }
        }

        $cart = $this->create($store);
        session(['cart_id' => $cart->getKey()]);

        return $cart;
    }

    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        $this->assertPositiveQuantity($quantity);

        return DB::transaction(function () use ($cart, $variantId, $quantity, $expectedVersion): CartLine {
            $lockedCart = $this->freshCart($cart);
            $this->assertExpectedVersion($lockedCart, $expectedVersion);
            $this->assertCartIsActive($lockedCart);

            $variant = $this->purchasableVariant($lockedCart, $variantId);
            $line = CartLine::withoutGlobalScopes()
                ->where('cart_id', $lockedCart->getKey())
                ->where('variant_id', $variant->getKey())
                ->first();
            $newQuantity = ($line?->quantity ?? 0) + $quantity;

            $this->assertAvailable($variant->inventoryItem, $newQuantity);

            if (! $line instanceof CartLine) {
                $line = new CartLine([
                    'cart_id' => $lockedCart->getKey(),
                    'variant_id' => $variant->getKey(),
                    'unit_price_amount' => $variant->price_amount,
                ]);
            }

            $this->fillLineAmounts($line, $newQuantity, $line->unit_price_amount ?: $variant->price_amount);
            $line->save();
            $this->incrementVersion($lockedCart);

            return $line->refresh();
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        if ($quantity <= 0) {
            $this->removeLine($cart, $lineId, $expectedVersion);

            return null;
        }

        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion): CartLine {
            $lockedCart = $this->freshCart($cart);
            $this->assertExpectedVersion($lockedCart, $expectedVersion);
            $this->assertCartIsActive($lockedCart);

            $line = CartLine::withoutGlobalScopes()
                ->where('cart_id', $lockedCart->getKey())
                ->findOrFail($lineId);
            $variant = $this->purchasableVariant($lockedCart, $line->variant_id);

            $this->assertAvailable($variant->inventoryItem, $quantity);
            $this->fillLineAmounts($line, $quantity, $line->unit_price_amount);
            $line->save();
            $this->incrementVersion($lockedCart);

            return $line->refresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion): void {
            $lockedCart = $this->freshCart($cart);
            $this->assertExpectedVersion($lockedCart, $expectedVersion);
            $this->assertCartIsActive($lockedCart);

            CartLine::withoutGlobalScopes()
                ->where('cart_id', $lockedCart->getKey())
                ->whereKey($lineId)
                ->delete();

            $this->incrementVersion($lockedCart);
        });
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        return DB::transaction(function () use ($guest, $customer): Cart {
            $guestCart = $this->freshCart($guest);
            $customerCart = $this->freshCart($customer);

            CartLine::withoutGlobalScopes()
                ->where('cart_id', $guestCart->getKey())
                ->get()
                ->each(function (CartLine $guestLine) use ($customerCart): void {
                    $existingLine = CartLine::withoutGlobalScopes()
                        ->where('cart_id', $customerCart->getKey())
                        ->where('variant_id', $guestLine->variant_id)
                        ->first();

                    if ($existingLine instanceof CartLine) {
                        $quantity = max($existingLine->quantity, $guestLine->quantity);
                        $this->fillLineAmounts($existingLine, $quantity, $existingLine->unit_price_amount);
                        $existingLine->save();
                        $guestLine->delete();

                        return;
                    }

                    $guestLine->forceFill(['cart_id' => $customerCart->getKey()])->save();
                });

            $guestCart->forceFill(['status' => CartStatus::Abandoned])->save();
            $this->incrementVersion($customerCart);

            return $customerCart->refresh();
        });
    }

    private function freshCart(Cart $cart): Cart
    {
        return Cart::withoutGlobalScopes()
            ->whereKey($cart->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function purchasableVariant(Cart $cart, int $variantId): ProductVariant
    {
        $variant = ProductVariant::withoutGlobalScopes()
            ->with([
                'product' => fn ($query) => $query->withoutGlobalScopes(),
                'inventoryItem' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->findOrFail($variantId);

        if ((int) $variant->product->store_id !== (int) $cart->store_id) {
            throw InvalidCartOperationException::because('Variant does not belong to this store.');
        }

        if ($variant->product->status !== ProductStatus::Active || $variant->product->published_at === null) {
            throw InvalidCartOperationException::because('Product is not active.');
        }

        if ($variant->status !== VariantStatus::Active) {
            throw InvalidCartOperationException::because('Variant is not active.');
        }

        if (! $variant->inventoryItem instanceof InventoryItem) {
            throw InvalidCartOperationException::because('Variant inventory is missing.');
        }

        return $variant;
    }

    private function assertAvailable(InventoryItem $item, int $quantity): void
    {
        if (! $this->inventory->checkAvailability($item, $quantity)) {
            throw \App\Exceptions\InsufficientInventoryException::forQuantity($item->availableQuantity(), $quantity);
        }
    }

    private function fillLineAmounts(CartLine $line, int $quantity, int $unitPrice): void
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

    private function incrementVersion(Cart $cart): void
    {
        $cart->forceFill([
            'cart_version' => $cart->cart_version + 1,
        ])->save();
    }

    private function assertExpectedVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $expectedVersion !== $cart->cart_version) {
            throw new CartVersionMismatchException($expectedVersion, $cart->cart_version);
        }
    }

    private function assertCartIsActive(Cart $cart): void
    {
        if ($cart->status !== CartStatus::Active) {
            throw InvalidCartOperationException::because('Cart is not active.');
        }
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw InvalidCartOperationException::because('Cart line quantity must be greater than zero.');
        }
    }
}
