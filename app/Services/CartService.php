<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductVariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CartService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::query()->create([
            'store_id' => (int) $store->id,
            'customer_id' => $customer?->id,
            'currency' => (string) $store->default_currency,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        $this->assertPositiveQuantity($quantity);

        /** @var CartLine $line */
        $line = DB::transaction(function () use ($cart, $variantId, $quantity, $expectedVersion): CartLine {
            $lockedCart = $this->lockCart($cart);

            $this->ensureMutableCart($lockedCart);
            $this->assertExpectedVersion($lockedCart, $expectedVersion);

            $variant = $this->resolvePurchasableVariant((int) $lockedCart->store_id, $variantId);

            /** @var CartLine|null $existingLine */
            $existingLine = CartLine::query()
                ->where('cart_id', '=', (int) $lockedCart->id)
                ->where('variant_id', '=', $variantId)
                ->lockForUpdate()
                ->first();

            $newQuantity = ((int) $existingLine?->quantity) + $quantity;
            $this->assertInventoryAvailableForQuantity($variant, $newQuantity);

            if ($existingLine === null) {
                $existingLine = CartLine::query()->create([
                    'cart_id' => (int) $lockedCart->id,
                    'variant_id' => $variantId,
                    'quantity' => $newQuantity,
                    'unit_price_amount' => (int) $variant->price_amount,
                    'line_subtotal_amount' => 0,
                    'line_discount_amount' => 0,
                    'line_total_amount' => 0,
                ]);
            } else {
                $existingLine->quantity = $newQuantity;
                $existingLine->unit_price_amount = (int) $variant->price_amount;
            }

            $this->recalculateLineAmounts($existingLine);
            $existingLine->save();

            $this->incrementVersion($lockedCart);
            $this->syncCart($cart, $lockedCart);

            return $existingLine->refresh();
        });

        return $line;
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): ?CartLine
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity must be zero or greater.');
        }

        if ($quantity === 0) {
            $this->removeLine($cart, $lineId, $expectedVersion);

            return null;
        }

        /** @var CartLine $line */
        $line = DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion): CartLine {
            $lockedCart = $this->lockCart($cart);

            $this->ensureMutableCart($lockedCart);
            $this->assertExpectedVersion($lockedCart, $expectedVersion);

            /** @var CartLine $line */
            $line = CartLine::query()
                ->where('id', '=', $lineId)
                ->where('cart_id', '=', (int) $lockedCart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $variant = $this->resolvePurchasableVariant((int) $lockedCart->store_id, (int) $line->variant_id);
            $this->assertInventoryAvailableForQuantity($variant, $quantity);

            $line->quantity = $quantity;
            $line->unit_price_amount = (int) $variant->price_amount;

            $this->recalculateLineAmounts($line);
            $line->save();

            $this->incrementVersion($lockedCart);
            $this->syncCart($cart, $lockedCart);

            return $line->refresh();
        });

        return $line;
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($cart, $lineId, $expectedVersion): void {
            $lockedCart = $this->lockCart($cart);

            $this->ensureMutableCart($lockedCart);
            $this->assertExpectedVersion($lockedCart, $expectedVersion);

            $line = CartLine::query()
                ->where('id', '=', $lineId)
                ->where('cart_id', '=', (int) $lockedCart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $line->delete();

            $this->incrementVersion($lockedCart);
            $this->syncCart($cart, $lockedCart);
        });
    }

    /**
     * @return array{subtotal: int, discount: int, total: int, line_count: int, item_count: int, currency: string}
     */
    public function computeCartTotals(Cart $cart): array
    {
        $cart->loadMissing('lines');

        $subtotal = 0;
        $discount = 0;
        $itemCount = 0;

        foreach ($cart->lines as $line) {
            $subtotal += (int) $line->line_subtotal_amount;
            $discount += (int) $line->line_discount_amount;
            $itemCount += (int) $line->quantity;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal - $discount,
            'line_count' => $cart->lines->count(),
            'item_count' => $itemCount,
            'currency' => (string) $cart->currency,
        ];
    }

    private function assertExpectedVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion === null) {
            return;
        }

        $currentVersion = (int) $cart->cart_version;

        if ($currentVersion !== $expectedVersion) {
            throw new CartVersionMismatchException(
                cartId: (int) $cart->id,
                expectedVersion: $expectedVersion,
                currentVersion: $currentVersion,
            );
        }
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }

    private function ensureMutableCart(Cart $cart): void
    {
        if ($cart->status !== CartStatus::Active) {
            throw new InvalidArgumentException(sprintf('Cart %d is not active.', (int) $cart->id));
        }
    }

    private function recalculateLineAmounts(CartLine $line): void
    {
        $lineSubtotal = (int) $line->unit_price_amount * (int) $line->quantity;

        $line->line_subtotal_amount = $lineSubtotal;

        if ((int) $line->line_discount_amount < 0) {
            $line->line_discount_amount = 0;
        }

        $line->line_total_amount = max(0, $lineSubtotal - (int) $line->line_discount_amount);
    }

    private function incrementVersion(Cart $cart): void
    {
        $cart->cart_version = (int) $cart->cart_version + 1;
        $cart->save();
    }

    private function lockCart(Cart $cart): Cart
    {
        /** @var Cart $locked */
        $locked = Cart::query()
            ->whereKey($cart->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    private function syncCart(Cart $target, Cart $source): void
    {
        $target->cart_version = (int) $source->cart_version;
        $target->status = $source->status;
        $target->currency = $source->currency;
        $target->updated_at = $source->updated_at;
    }

    private function assertInventoryAvailableForQuantity(ProductVariant $variant, int $quantity): void
    {
        $inventoryItem = $variant->inventoryItem;

        if ($inventoryItem === null) {
            return;
        }

        if ($this->inventoryService->checkAvailability($inventoryItem, $quantity)) {
            return;
        }

        throw InsufficientInventoryException::forItem($inventoryItem, $quantity);
    }

    private function resolvePurchasableVariant(int $storeId, int $variantId): ProductVariant
    {
        /** @var ProductVariant|null $variant */
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('status', '=', ProductVariantStatus::Active)
            ->whereHas('product', static function ($query) use ($storeId): void {
                $query
                    ->where('store_id', '=', $storeId)
                    ->where('status', '=', ProductStatus::Active);
            })
            ->with('inventoryItem')
            ->first();

        if ($variant === null) {
            throw (new ModelNotFoundException)->setModel(ProductVariant::class, [$variantId]);
        }

        return $variant;
    }
}
