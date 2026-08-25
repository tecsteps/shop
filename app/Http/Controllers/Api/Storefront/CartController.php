<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function store(Request $request)
    {
        $request->validate(['currency' => ['sometimes', 'string', 'size:3']]);

        $cart = $this->cartService->create(app('current_store'));
        session(['cart_id' => $cart->id]);

        return response()->json($this->payload($cart), 201);
    }

    public function show(Cart $cart)
    {
        return response()->json($this->payload($cart->load('lines.variant.product')));
    }

    public function addLine(Request $request, Cart $cart)
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        try {
            $this->cartService->addLine($cart, $validated['variant_id'], $validated['quantity']);
        } catch (InsufficientInventoryException) {
            throw ValidationException::withMessages(['variant_id' => ['The selected variant is out of stock.']]);
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages(['variant_id' => ['The selected variant is invalid.']]);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['variant_id' => [$e->getMessage()]]);
        }

        return response()->json($this->payload($cart->fresh()->load('lines.variant.product')), 201);
    }

    public function updateLine(Request $request, Cart $cart, int $line)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['required', 'integer'],
        ]);

        $this->assertVersion($cart, (int) $validated['cart_version']);

        try {
            $this->cartService->updateLineQuantity($cart, $line, $validated['quantity']);
        } catch (InsufficientInventoryException) {
            throw ValidationException::withMessages(['quantity' => ['The selected variant is out of stock.']]);
        }

        return response()->json($this->payload($cart->fresh()->load('lines.variant.product')));
    }

    public function removeLine(Request $request, Cart $cart, int $line)
    {
        $validated = $request->validate(['cart_version' => ['required', 'integer']]);

        $this->assertVersion($cart, (int) $validated['cart_version']);

        $this->cartService->removeLine($cart, $line);

        return response()->json($this->payload($cart->fresh()->load('lines.variant.product')));
    }

    private function assertVersion(Cart $cart, int $expectedVersion): void
    {
        if ($cart->cart_version !== $expectedVersion) {
            throw new CartVersionMismatchException;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Cart $cart): array
    {
        $lines = $cart->lines->map(fn ($line) => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_title' => $line->variant?->product?->title,
            'variant_title' => $line->variant?->optionValues->pluck('value')->join(' / '),
            'sku' => $line->variant?->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_subtotal_amount' => $line->line_subtotal_amount,
            'line_discount_amount' => $line->line_discount_amount,
            'line_total_amount' => $line->line_total_amount,
            'requires_shipping' => $line->variant?->requires_shipping,
        ])->all();

        $subtotal = $cart->lines->sum(fn ($line) => $line->unit_price_amount * $line->quantity);

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'cart_version' => $cart->cart_version,
            'status' => $cart->status,
            'lines' => $lines,
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'currency' => $cart->currency,
                'line_count' => $cart->lines->count(),
                'item_count' => $cart->lines->sum('quantity'),
            ],
        ];
    }
}
