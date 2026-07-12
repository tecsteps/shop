<?php

namespace App\Services;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\DomainException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Store;
use BackedEnum;
use Illuminate\Support\Facades\DB;

final class CartService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function create(Store $store, ?Customer $customer = null): Cart
    {
        return Cart::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'currency' => $store->default_currency,
            'cart_version' => 1,
            'status' => 'active',
        ]);
    }

    public function getOrCreateForSession(Store $store, ?Customer $customer = null): Cart
    {
        if ($customer !== null) {
            $customerCart = Cart::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($customerCart !== null) {
                return $customerCart;
            }
        }

        $cartId = session('cart_id');
        if ($cartId !== null) {
            $cart = Cart::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', 'active')
                ->find($cartId);

            if ($cart !== null) {
                return $cart;
            }
        }

        $cart = $this->create($store, $customer);
        session(['cart_id' => $cart->id]);

        return $cart;
    }

    public function addLine(Cart $cart, int|ProductVariant $variant, int $quantity = 1, ?int $expectedVersion = null): CartLine
    {
        $this->assertVersion($cart, $expectedVersion);
        $this->positive($quantity);
        $variant = $variant instanceof ProductVariant
            ? $variant
            : ProductVariant::withoutGlobalScopes()->with(['product', 'inventoryItem'])->findOrFail($variant);
        $variant->loadMissing(['product', 'inventoryItem']);

        if ((int) $variant->product->store_id !== (int) $cart->store_id) {
            throw new DomainException('The selected variant does not belong to this store.');
        }

        if ($this->enumValue($variant->product->status) !== 'active' || $this->enumValue($variant->status) !== 'active') {
            throw new DomainException('This product is not available.');
        }

        return DB::transaction(function () use ($cart, $variant, $quantity): CartLine {
            $line = CartLine::query()->where('cart_id', $cart->id)->where('variant_id', $variant->id)->first();
            $newQuantity = $quantity + ($line?->quantity ?? 0);

            if ($variant->inventoryItem !== null && ! $this->inventory->checkAvailability($variant->inventoryItem, $newQuantity)) {
                throw new \App\Exceptions\InsufficientInventoryException('The requested quantity is not available.');
            }

            $amounts = $this->amounts((int) $variant->price_amount, $newQuantity);
            $line ??= new CartLine(['cart_id' => $cart->id, 'variant_id' => $variant->id]);
            $line->fill(['quantity' => $newQuantity, ...$amounts])->save();
            $this->bumpVersion($cart);

            return $line->refresh();
        });
    }

    public function updateLineQuantity(Cart $cart, int $lineId, int $quantity, ?int $expectedVersion = null): CartLine
    {
        $this->assertVersion($cart, $expectedVersion);
        if ($quantity === 0) {
            $this->removeLine($cart, $lineId, $expectedVersion);

            return new CartLine;
        }
        $this->positive($quantity);

        return DB::transaction(function () use ($cart, $lineId, $quantity): CartLine {
            $line = CartLine::query()->where('cart_id', $cart->id)->with('variant.inventoryItem')->findOrFail($lineId);

            if ($line->variant->inventoryItem !== null && ! $this->inventory->checkAvailability($line->variant->inventoryItem, $quantity)) {
                throw new \App\Exceptions\InsufficientInventoryException('The requested quantity is not available.');
            }

            $line->fill(['quantity' => $quantity, ...$this->amounts((int) $line->variant->price_amount, $quantity)])->save();
            $this->bumpVersion($cart);

            return $line->refresh();
        });
    }

    public function removeLine(Cart $cart, int $lineId, ?int $expectedVersion = null): void
    {
        $this->assertVersion($cart, $expectedVersion);
        DB::transaction(function () use ($cart, $lineId): void {
            CartLine::query()->where('cart_id', $cart->id)->findOrFail($lineId)->delete();
            $this->bumpVersion($cart);
        });
    }

    public function mergeOnLogin(Cart $guest, Cart $customer): Cart
    {
        if ((int) $guest->store_id !== (int) $customer->store_id) {
            throw new DomainException('Carts from different stores cannot be merged.');
        }

        return DB::transaction(function () use ($guest, $customer): Cart {
            $guest->load('lines');
            foreach ($guest->lines as $line) {
                $target = CartLine::query()->where('cart_id', $customer->id)->where('variant_id', $line->variant_id)->first();
                if ($target !== null) {
                    $target->quantity += $line->quantity;
                    $target->fill($this->amounts((int) $target->unit_price_amount, (int) $target->quantity))->save();
                } else {
                    $line->cart_id = $customer->id;
                    $line->save();
                }
            }

            $guest->update(['status' => 'abandoned']);
            $this->bumpVersion($customer);
            session()->forget('cart_id');

            return $customer->refresh()->load('lines');
        });
    }

    public function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && (int) $cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException($cart->fresh('lines') ?? $cart);
        }
    }

    /** @return array{unit_price_amount: int, line_subtotal_amount: int, line_discount_amount: int, line_total_amount: int} */
    private function amounts(int $unitPrice, int $quantity): array
    {
        $subtotal = $unitPrice * $quantity;

        return [
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ];
    }

    private function bumpVersion(Cart $cart): void
    {
        $cart->increment('cart_version');
        $cart->refresh();
    }

    private function positive(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least one.');
        }
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
