<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        /** @var Store $store */
        $store = app('current_store');
        $cart = $this->cartService->create($store);

        return response()->json($this->formatCart($cart), 201);
    }

    public function show(Request $request, Cart $cart): JsonResponse
    {
        $this->verifyStoreOwnership($cart);

        $cart->load('lines.variant.product');

        return response()->json($this->formatCart($cart));
    }

    public function addLine(Request $request, Cart $cart): JsonResponse
    {
        $this->verifyStoreOwnership($cart);

        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:9999',
        ]);

        $this->cartService->addLine($cart, $validated['variant_id'], $validated['quantity']);
        $cart->refresh()->load('lines.variant.product');

        return response()->json($this->formatCart($cart), 201);
    }

    public function updateLine(Request $request, Cart $cart, CartLine $line): JsonResponse
    {
        $this->verifyStoreOwnership($cart);
        abort_unless($line->cart_id === $cart->id, 404);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:9999',
        ]);

        $this->cartService->updateLineQuantity($cart, $line->id, $validated['quantity']);
        $cart->refresh()->load('lines.variant.product');

        return response()->json($this->formatCart($cart));
    }

    public function removeLine(Request $request, Cart $cart, CartLine $line): JsonResponse
    {
        $this->verifyStoreOwnership($cart);
        abort_unless($line->cart_id === $cart->id, 404);

        $this->cartService->removeLine($cart, $line->id);
        $cart->refresh()->load('lines.variant.product');

        return response()->json($this->formatCart($cart));
    }

    private function verifyStoreOwnership(Cart $cart): void
    {
        /** @var Store $store */
        $store = app('current_store');
        abort_unless($cart->store_id === $store->id, 404);
    }

    private function formatCart(Cart $cart): array
    {
        $cart->loadMissing('lines.variant.product');

        $lines = $cart->lines->map(fn (CartLine $line) => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_title' => $line->variant?->product?->title,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price,
            'line_total_amount' => $line->unit_price * $line->quantity,
        ]);

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'status' => $cart->status->value,
            'lines' => $lines,
            'totals' => [
                'subtotal' => $lines->sum('line_total_amount'),
                'total' => $lines->sum('line_total_amount'),
                'currency' => $cart->currency,
                'line_count' => $lines->count(),
                'item_count' => $lines->sum('quantity'),
            ],
            'created_at' => $cart->created_at,
            'updated_at' => $cart->updated_at,
        ];
    }
}
