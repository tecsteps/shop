<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontCartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['currency' => ['nullable', 'string', 'size:3']]);
        $cart = $this->carts->create(app('current_store'), $request->user('customer'));

        if (isset($data['currency'])) {
            $cart->update(['currency' => strtoupper($data['currency'])]);
        }

        $request->session()->put(['cart_id' => $cart->getKey(), 'cart_id_'.app('current_store')->getKey() => $cart->getKey()]);

        return response()->json($this->payload($cart->refresh()), 201);
    }

    public function show(int $cartId): JsonResponse
    {
        return response()->json($this->payload($this->cart($cartId)));
    }

    public function addLine(Request $request, int $cartId): JsonResponse
    {
        $data = $request->validate(['variant_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1', 'max:9999'], 'cart_version' => ['nullable', 'integer'], 'expected_version' => ['nullable', 'integer']]);
        $cart = $this->cart($cartId);

        try {
            $this->carts->assertVersion($cart, $data['cart_version'] ?? $data['expected_version'] ?? null);
            $this->carts->addLine($cart, $data['variant_id'], $data['quantity']);
        } catch (CartVersionConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'cart' => $this->payload($cart->refresh())], 409);
        } catch (InsufficientInventoryException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'insufficient_inventory'], 422);
        }

        return response()->json($this->payload($this->cart($cartId)), 201);
    }

    public function updateLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:9999'], 'cart_version' => ['required', 'integer']]);
        $cart = $this->cart($cartId);

        try {
            $this->carts->assertVersion($cart, $data['cart_version']);
            $this->carts->updateLineQuantity($cart, $lineId, $data['quantity']);
        } catch (CartVersionConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'cart' => $this->payload($cart->refresh())], 409);
        } catch (InsufficientInventoryException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'insufficient_inventory'], 422);
        }

        return response()->json($this->payload($this->cart($cartId)));
    }

    public function removeLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $data = $request->validate(['cart_version' => ['required', 'integer']]);
        $cart = $this->cart($cartId);

        try {
            $this->carts->assertVersion($cart, $data['cart_version']);
            $this->carts->removeLine($cart, $lineId);
        } catch (CartVersionConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'cart' => $this->payload($cart->refresh())], 409);
        }

        return response()->json($this->payload($this->cart($cartId)));
    }

    private function cart(int $cartId): Cart
    {
        $cart = Cart::query()
            ->where('store_id', app('current_store')->getKey())
            ->where('status', 'active')
            ->with(['lines.variant.product.media', 'lines.variant.inventory'])
            ->findOrFail($cartId);
        $customerId = request()->user('customer')?->getKey();

        if ($customerId !== null) {
            abort_unless((int) $cart->customer_id === (int) $customerId, 404);
        }

        return $cart;
    }

    /** @return array<string, mixed> */
    private function payload(Cart $cart): array
    {
        $lines = $cart->lines->map(fn ($line): array => ['id' => $line->id, 'variant_id' => $line->variant_id, 'product_title' => $line->variant->product->title, 'variant_title' => $line->variant->title, 'sku' => $line->variant->sku, 'quantity' => $line->quantity, 'unit_price_amount' => $line->unit_price_amount, 'line_subtotal_amount' => $line->line_subtotal_amount, 'line_discount_amount' => $line->line_discount_amount, 'line_total_amount' => $line->line_total_amount, 'image_url' => $line->variant->product->media->first()?->url, 'requires_shipping' => $line->variant->requires_shipping, 'available_quantity' => $line->variant->inventory?->availableQuantity() ?? 0])->values()->all();
        $subtotal = (int) collect($lines)->sum('line_total_amount');

        return ['id' => $cart->id, 'store_id' => $cart->store_id, 'customer_id' => $cart->customer_id, 'currency' => $cart->currency, 'cart_version' => $cart->cart_version, 'status' => $cart->status, 'lines' => $lines, 'totals' => ['subtotal' => $subtotal, 'discount' => 0, 'total' => $subtotal, 'currency' => $cart->currency, 'line_count' => count($lines), 'item_count' => (int) collect($lines)->sum('quantity')], 'created_at' => $cart->created_at, 'updated_at' => $cart->updated_at];
    }
}
