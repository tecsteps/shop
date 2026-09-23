<?php

namespace App\Services;

use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly DiscountCalculator $discountCalculator,
    ) {}

    public function getOrCreateActiveCart(Store $store, ?Customer $customer = null): Cart
    {
        if ($customer) {
            return Cart::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'customer_id' => $customer->id, 'status' => 'active'],
                ['currency' => $store->default_currency, 'cart_version' => 1],
            )->load('lines.variant.product', 'lines.variant.inventoryItem');
        }

        $sessionCartId = Session::get('cart_id');
        $cart = $sessionCartId
            ? Cart::withoutGlobalScopes()->where('store_id', $store->id)->where('status', 'active')->find($sessionCartId)
            : null;

        if (! $cart) {
            $cart = Cart::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'currency' => $store->default_currency,
                'cart_version' => 1,
                'status' => 'active',
            ]);
            Session::put('cart_id', $cart->id);
        }

        return $cart->load('lines.variant.product', 'lines.variant.inventoryItem');
    }

    public function findActiveCart(Store $store, ?Customer $customer = null): ?Cart
    {
        $query = Cart::withoutGlobalScopes()->where('store_id', $store->id)->where('status', 'active');

        if ($customer) {
            $query->where('customer_id', $customer->id);
        } else {
            $query->whereKey(Session::get('cart_id', 0))->whereNull('customer_id');
        }

        return $query->with('lines.variant.product', 'lines.variant.inventoryItem')->first();
    }

    public function add(Cart $cart, ProductVariant $variant, int $quantity, ?int $expectedVersion = null): Cart
    {
        if ($quantity < 1 || $quantity > 9999) {
            throw ValidationException::withMessages(['quantity' => 'Choose a quantity between 1 and 9999.']);
        }

        return DB::transaction(function () use ($cart, $variant, $quantity, $expectedVersion): Cart {
            $lockedCart = Cart::withoutGlobalScopes()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $this->assertVersion($lockedCart, $expectedVersion);

            $variant->loadMissing(['product', 'inventoryItem']);

            if (! $variant->product || (int) $variant->product->store_id !== (int) $lockedCart->store_id) {
                abort(404);
            }

            if ($variant->product->status !== 'active' || $variant->status !== 'active') {
                throw ValidationException::withMessages(['variant' => 'This product is not available for purchase.']);
            }

            $line = $lockedCart->lines()->where('variant_id', $variant->id)->first();
            $newQuantity = (int) ($line?->quantity ?? 0) + $quantity;

            if ($newQuantity > 9999) {
                throw ValidationException::withMessages(['quantity' => 'A cart line cannot exceed 9999 items.']);
            }

            if ($variant->inventoryItem && ! $this->inventory->checkAvailability($variant->inventoryItem, $newQuantity)) {
                throw new InsufficientInventoryException('There is not enough stock available for this quantity.');
            }

            $subtotal = $variant->price_amount * $newQuantity;
            $line?->delete();
            $lockedCart->lines()->create([
                'variant_id' => $variant->id,
                'quantity' => $newQuantity,
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $subtotal,
                'line_discount_amount' => 0,
                'line_total_amount' => $subtotal,
            ]);
            $lockedCart->increment('cart_version');
            $this->refreshDiscountAllocations($lockedCart);

            return $lockedCart->refresh()->load('lines.variant.product', 'lines.variant.inventoryItem');
        });
    }

    public function updateQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): Cart
    {
        return DB::transaction(function () use ($cart, $lineId, $quantity, $expectedVersion): Cart {
            $lockedCart = Cart::withoutGlobalScopes()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $this->assertVersion($lockedCart, $expectedVersion);
            $line = $lockedCart->lines()->with('variant.inventoryItem')->findOrFail($lineId);

            if ($quantity <= 0) {
                $line->delete();
            } else {
                if ($quantity > 9999) {
                    throw ValidationException::withMessages(['quantity' => 'Choose a quantity between 1 and 9999.']);
                }

                if ($line->variant->inventoryItem && ! $this->inventory->checkAvailability($line->variant->inventoryItem, $quantity)) {
                    throw new InsufficientInventoryException('There is not enough stock available for this quantity.');
                }

                $line->quantity = $quantity;
                $line->line_subtotal_amount = $line->unit_price_amount * $quantity;
                $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
                $line->save();
            }

            $lockedCart->increment('cart_version');
            $this->refreshDiscountAllocations($lockedCart);

            return $lockedCart->refresh()->load('lines.variant.product', 'lines.variant.inventoryItem');
        });
    }

    public function remove(Cart $cart, int $lineId, ?int $expectedVersion = null): Cart
    {
        return $this->updateQuantity($cart, $lineId, 0, $expectedVersion);
    }

    public function mergeGuestCart(Cart $guestCart, Customer $customer): Cart
    {
        return DB::transaction(function () use ($guestCart, $customer): Cart {
            $customerCart = Cart::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $guestCart->store_id, 'customer_id' => $customer->id, 'status' => 'active'],
                ['currency' => $guestCart->currency, 'cart_version' => 1],
            );

            foreach ($guestCart->lines()->with('variant')->get() as $guestLine) {
                $customerLine = $customerCart->lines()->where('variant_id', $guestLine->variant_id)->first();

                if ($customerLine) {
                    $customerLine->quantity = max($customerLine->quantity, $guestLine->quantity);
                    $customerLine->unit_price_amount = $guestLine->variant->price_amount;
                    $customerLine->line_subtotal_amount = $customerLine->unit_price_amount * $customerLine->quantity;
                    $customerLine->line_total_amount = $customerLine->line_subtotal_amount;
                    $customerLine->save();
                    $guestLine->delete();
                } else {
                    $guestLine->cart_id = $customerCart->id;
                    $guestLine->unit_price_amount = $guestLine->variant->price_amount;
                    $guestLine->line_subtotal_amount = $guestLine->unit_price_amount * $guestLine->quantity;
                    $guestLine->line_total_amount = $guestLine->line_subtotal_amount;
                    $guestLine->save();
                }
            }

            $guestCart->status = 'abandoned';
            $guestCart->save();
            $customerCart->increment('cart_version');
            if (! $customerCart->discount_code && $guestCart->discount_code) {
                $customerCart->forceFill(['discount_code' => $guestCart->discount_code])->save();
            }
            $this->refreshDiscountAllocations($customerCart);
            Session::forget('cart_id');

            return $customerCart->refresh()->load('lines.variant.product');
        });
    }

    public function applyDiscount(Cart $cart, string $code): Cart
    {
        $discount = Discount::query()
            ->where('store_id', $cart->store_id)
            ->whereRaw('upper(code) = ?', [strtoupper(trim($code))])
            ->first();

        if (! $discount) {
            throw ValidationException::withMessages(['discount_code' => 'This discount code is not valid.']);
        }
        $cart->load('lines.variant.product.collections');
        $lines = $cart->lines->map(fn ($line): array => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_id' => $line->variant?->product_id,
            'collection_ids' => $line->variant?->product?->collections?->pluck('id')->all() ?? [],
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_subtotal_amount' => $line->line_subtotal_amount,
        ])->all();
        $subtotal = array_sum(array_column($lines, 'line_subtotal_amount'));
        $result = $this->discountCalculator->calculate($discount, $lines, $subtotal, $cart->customer_id !== null);

        DB::transaction(function () use ($cart, $discount, $result): void {
            foreach ($cart->lines as $line) {
                $amount = (int) ($result['allocations'][$line->variant_id] ?? 0);
                $line->forceFill(['line_discount_amount' => $amount, 'line_total_amount' => $line->line_subtotal_amount - $amount])->save();
            }

            $cart->forceFill(['discount_code' => $discount->code])->save();
            $cart->increment('cart_version');
        });

        return $cart->refresh()->load('lines.variant.product');
    }

    public function removeDiscount(Cart $cart): Cart
    {
        $cart->lines()->update(['line_discount_amount' => 0, 'line_total_amount' => DB::raw('line_subtotal_amount')]);
        $cart->forceFill(['discount_code' => null])->save();
        $cart->increment('cart_version');

        return $cart->refresh()->load('lines.variant.product');
    }

    /** @return array{items: int, subtotal: int} */
    public function summary(Cart $cart): array
    {
        return [
            'items' => (int) $cart->lines()->sum('quantity'),
            'subtotal' => (int) $cart->lines()->sum('line_subtotal_amount'),
        ];
    }

    private function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && $expectedVersion !== (int) $cart->cart_version) {
            throw new CartVersionConflictException((int) $cart->cart_version);
        }
    }

    private function refreshDiscountAllocations(Cart $cart): void
    {
        $cart->load('lines.variant.product.collections');

        if (! $cart->discount_code || $cart->lines->isEmpty()) {
            $this->clearDiscountAllocations($cart);

            return;
        }

        $discount = Discount::query()
            ->where('store_id', $cart->store_id)
            ->whereRaw('upper(code) = ?', [strtoupper(trim($cart->discount_code))])
            ->first();

        if (! $discount) {
            $this->clearDiscountAllocations($cart);

            return;
        }

        $lines = $cart->lines->map(fn ($line): array => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_id' => $line->variant?->product_id,
            'collection_ids' => $line->variant?->product?->collections?->pluck('id')->all() ?? [],
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_subtotal_amount' => $line->line_subtotal_amount,
        ])->all();

        try {
            $result = $this->discountCalculator->calculate(
                $discount,
                $lines,
                array_sum(array_column($lines, 'line_subtotal_amount')),
                $cart->customer_id !== null,
            );
        } catch (ValidationException) {
            $this->clearDiscountAllocations($cart);

            return;
        }

        foreach ($cart->lines as $line) {
            $amount = (int) ($result['allocations'][$line->variant_id] ?? 0);
            $line->forceFill([
                'line_discount_amount' => $amount,
                'line_total_amount' => max(0, $line->line_subtotal_amount - $amount),
            ])->save();
        }
    }

    private function clearDiscountAllocations(Cart $cart): void
    {
        foreach ($cart->lines as $line) {
            if ($line->line_discount_amount !== 0) {
                $line->forceFill([
                    'line_discount_amount' => 0,
                    'line_total_amount' => $line->line_subtotal_amount,
                ])->save();
            }
        }

        if ($cart->discount_code !== null) {
            $cart->forceFill(['discount_code' => null])->save();
        }
    }
}
