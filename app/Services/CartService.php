<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function sessionKey(Store $store): string
    {
        return 'cart_id_store_'.$store->id;
    }

    public function create(Store $store, ?int $customerId = null, ?string $currency = null): Cart
    {
        return Cart::query()->create([
            'store_id' => $store->id,
            'customer_id' => $customerId,
            'currency' => strtoupper($currency ?: $store->default_currency),
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ]);
    }

    public function getOrCreateBySession(
        Store $store,
        ?int $sessionCartId,
        ?int $customerId = null,
        ?string $currency = null,
    ): Cart {
        if ($sessionCartId) {
            $existing = Cart::query()
                ->with(['lines.variant.product', 'lines.variant.inventoryItem'])
                ->where('store_id', $store->id)
                ->where('status', CartStatus::Active)
                ->whereKey($sessionCartId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return $this->create($store, $customerId, $currency);
    }

    public function findForStore(Store $store, int $cartId): Cart
    {
        return Cart::query()
            ->with(['lines.variant.product', 'lines.variant.inventoryItem'])
            ->where('store_id', $store->id)
            ->whereKey($cartId)
            ->firstOrFail();
    }

    public function addLine(Cart $cart, int $variantId, int $quantity, ?int $expectedVersion = null): Cart
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity, $expectedVersion): Cart {
            $cart = $this->refreshCart($cart->id);
            $this->assertExpectedVersion($cart, $expectedVersion);

            $variant = $this->resolveVariant($cart, $variantId);
            $line = $cart->lines()->where('variant_id', $variant->id)->first();

            if (! $line) {
                $line = new CartLine([
                    'variant_id' => $variant->id,
                    'quantity' => 0,
                ]);
            }

            $newQuantity = $line->quantity + $quantity;
            $this->inventoryService->assertCanReserve($variant, $newQuantity);

            $line->quantity = $newQuantity;
            $line->unit_price_amount = $variant->price_amount;
            $this->applyLineAmounts($line);

            $cart->lines()->save($line);

            $this->incrementVersion($cart);

            return $this->refreshCart($cart->id);
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): Cart
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion): Cart {
            $cart = $this->refreshCart($cart->id);
            $this->assertExpectedVersion($cart, $expectedVersion);

            $line = $cart->lines()->with('variant.product', 'variant.inventoryItem')->findOrFail($lineId);

            if ($quantity <= 0) {
                $line->delete();
                $this->incrementVersion($cart);

                return $this->refreshCart($cart->id);
            }

            $this->inventoryService->assertCanReserve($line->variant, $quantity);

            $line->quantity = $quantity;
            $line->unit_price_amount = $line->variant->price_amount;
            $this->applyLineAmounts($line);
            $line->save();

            $this->incrementVersion($cart);

            return $this->refreshCart($cart->id);
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): Cart
    {
        return DB::transaction(function () use ($cart, $lineId, $expectedVersion): Cart {
            $cart = $this->refreshCart($cart->id);
            $this->assertExpectedVersion($cart, $expectedVersion);

            $cart->lines()->whereKey($lineId)->firstOrFail()->delete();

            $this->incrementVersion($cart);

            return $this->refreshCart($cart->id);
        });
    }

    public function resetDiscounts(Cart $cart): Cart
    {
        return DB::transaction(function () use ($cart): Cart {
            $cart = $this->refreshCart($cart->id);

            foreach ($cart->lines as $line) {
                $line->line_discount_amount = 0;
                $this->applyLineAmounts($line);
                $line->save();
            }

            return $this->refreshCart($cart->id);
        });
    }

    public function totals(Cart $cart): array
    {
        $cart->loadMissing('lines');

        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $discount = (int) $cart->lines->sum('line_discount_amount');
        $total = (int) $cart->lines->sum('line_total_amount');
        $itemCount = (int) $cart->lines->sum('quantity');

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'currency' => $cart->currency,
            'line_count' => $cart->lines->count(),
            'item_count' => $itemCount,
        ];
    }

    private function refreshCart(int $cartId): Cart
    {
        return Cart::query()
            ->with(['lines.variant.product', 'lines.variant.inventoryItem'])
            ->findOrFail($cartId);
    }

    private function assertExpectedVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion === null) {
            return;
        }

        if ($cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException($expectedVersion, $cart->cart_version, $cart);
        }
    }

    private function resolveVariant(Cart $cart, int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->with(['product', 'inventoryItem'])
            ->whereKey($variantId)
            ->firstOrFail();

        if ($variant->product->store_id !== $cart->store_id) {
            throw new \InvalidArgumentException('Variant does not belong to this store.');
        }

        if ($variant->product->status !== ProductStatus::Active) {
            throw new \InvalidArgumentException('Product is not active.');
        }

        if ($variant->status !== VariantStatus::Active) {
            throw new \InvalidArgumentException('Variant is not active.');
        }

        return $variant;
    }

    private function applyLineAmounts(CartLine $line): void
    {
        $line->line_subtotal_amount = $line->unit_price_amount * $line->quantity;
        $line->line_discount_amount = max(0, min($line->line_discount_amount, $line->line_subtotal_amount));
        $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
    }

    private function incrementVersion(Cart $cart): void
    {
        $cart->cart_version += 1;
        $cart->status = CartStatus::Active;
        $cart->save();
    }
}
