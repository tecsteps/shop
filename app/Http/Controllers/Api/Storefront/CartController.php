<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\DomainException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ]);
        $store = app('current_store');
        $customer = auth('customer')->user();
        $cart = $this->carts->create($store, $customer, $validated['currency'] ?? null);
        $request->session()->put('cart_id', $cart->id);
        $request->session()->put('api_cart_ids', collect($request->session()->get('api_cart_ids', []))
            ->push($cart->id)->unique()->take(-20)->values()->all());

        return response()->json($this->data($cart), 201);
    }

    public function show(Request $request, int $cartId): JsonResponse
    {
        return response()->json($this->data($this->cart($request, $cartId)));
    }

    public function addLine(Request $request, int $cartId): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        try {
            $cart = $this->cart($request, $cartId);
            $variant = ProductVariant::withoutGlobalScopes()
                ->whereKey($validated['variant_id'])
                ->where('status', 'active')
                ->whereHas('product', fn ($query) => $query->withoutGlobalScopes()
                    ->where('store_id', app('current_store')->id)
                    ->where('status', 'active'))
                ->first();
            if ($variant === null) {
                throw ValidationException::withMessages(['variant_id' => 'The selected variant is not available.']);
            }
            $this->carts->addLine($cart, $variant, $validated['quantity'], $validated['expected_version'] ?? null);

            return response()->json($this->data($cart->refresh()), 201);
        } catch (CartVersionMismatchException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'data' => $this->data($exception->cart)], 409);
        } catch (InsufficientInventoryException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['variant_id' => $exception->getMessage()]);
        }
    }

    public function updateLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'cart_version' => ['required_without:expected_version', 'integer', 'min:1'],
        ]);
        try {
            $cart = $this->cart($request, $cartId);
            $this->carts->updateLineQuantity($cart, $lineId, $validated['quantity'], $validated['expected_version'] ?? $validated['cart_version'] ?? null);

            return response()->json($this->data($cart->refresh()));
        } catch (CartVersionMismatchException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'data' => $this->data($exception->cart)], 409);
        } catch (InsufficientInventoryException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['cart' => $exception->getMessage()]);
        }
    }

    public function removeLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'cart_version' => ['required_without:expected_version', 'integer', 'min:1'],
        ]);
        try {
            $cart = $this->cart($request, $cartId);
            $this->carts->removeLine($cart, $lineId, $validated['expected_version'] ?? $validated['cart_version'] ?? null);

            return response()->json($this->data($cart->refresh()));
        } catch (CartVersionMismatchException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'data' => $this->data($exception->cart)], 409);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['cart' => $exception->getMessage()]);
        }
    }

    private function cart(Request $request, int $id): Cart
    {
        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->with(['lines.variant.product.media', 'lines.variant.optionValues', 'lines.variant.inventoryItem'])
            ->findOrFail($id);
        $customerId = auth('customer')->id();
        $sessionCartIds = collect($request->session()->get('api_cart_ids', []))
            ->push($request->session()->get('cart_id'))
            ->filter()
            ->map(fn ($value): int => (int) $value);
        $ownedByCustomer = $customerId !== null && (int) $cart->customer_id === (int) $customerId;
        $ownedBySession = $cart->customer_id === null && $sessionCartIds->contains((int) $cart->id);

        abort_unless($ownedByCustomer || $ownedBySession, 404);

        return $cart;
    }

    /** @return array<string, mixed> */
    private function data(Cart $cart): array
    {
        $cart->loadMissing(['lines.variant.product.media', 'lines.variant.optionValues', 'lines.variant.inventoryItem']);
        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $discount = (int) $cart->lines->sum('line_discount_amount');

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'status' => $cart->status instanceof \BackedEnum ? $cart->status->value : $cart->status,
            'cart_version' => $cart->cart_version,
            'lines' => $cart->lines->map(fn ($line): array => [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant?->product?->title,
                'variant_title' => $line->variant?->optionValues->pluck('value')->implode(' / ') ?: 'Default',
                'sku' => $line->variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_subtotal_amount' => $line->line_subtotal_amount,
                'line_discount_amount' => $line->line_discount_amount,
                'line_total_amount' => $line->line_total_amount,
                'image_url' => $line->variant?->product?->media->first() === null ? null : url(Storage::disk('public')->url($line->variant->product->media->first()->storage_key)),
                'requires_shipping' => (bool) $line->variant?->requires_shipping,
                'available_quantity' => $line->variant?->inventoryItem?->availableQuantity(),
            ])->values(),
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => max(0, $subtotal - $discount),
                'currency' => $cart->currency,
                'line_count' => $cart->lines->count(),
                'item_count' => (int) $cart->lines->sum('quantity'),
            ],
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ];
    }
}
