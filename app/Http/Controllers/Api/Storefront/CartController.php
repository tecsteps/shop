<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionConflictException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function store(): JsonResponse
    {
        /** @var Store $store */
        $store = app('current_store');

        return (new CartResource($this->cartService->create($store)))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Cart $cart): CartResource
    {
        $this->ensureCurrentStore($cart);

        return new CartResource($cart->load('lines.variant.product'));
    }

    public function addLine(Request $request, Cart $cart): CartResource|JsonResponse
    {
        $this->ensureCurrentStore($cart);
        $validated = $request->validate(['variant_id' => ['required', 'integer', 'exists:product_variants,id'], 'quantity' => ['required', 'integer', 'min:1'], 'expected_version' => ['sometimes', 'integer', 'min:1']]);

        return $this->mutate($cart, fn () => $this->cartService->addLine($cart, $validated['variant_id'], $validated['quantity'], $validated['expected_version'] ?? null));
    }

    public function updateLine(Request $request, Cart $cart, int $line): CartResource|JsonResponse
    {
        $this->ensureCurrentStore($cart);
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:0'], 'expected_version' => ['sometimes', 'integer', 'min:1']]);

        return $this->mutate($cart, fn () => $this->cartService->updateLineQuantity($cart, $line, $validated['quantity'], $validated['expected_version'] ?? null));
    }

    public function destroyLine(Request $request, Cart $cart, int $line): CartResource|JsonResponse
    {
        $this->ensureCurrentStore($cart);

        return $this->mutate($cart, fn () => $this->cartService->removeLine($cart, $line, $request->integer('expected_version') ?: null));
    }

    private function mutate(Cart $cart, callable $mutation): CartResource|JsonResponse
    {
        try {
            $mutation();
        } catch (CartVersionConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'cart_version_conflict', 'cart' => new CartResource($cart->refresh()->load('lines'))], Response::HTTP_CONFLICT);
        }

        return new CartResource($cart->refresh()->load('lines.variant.product'));
    }

    private function ensureCurrentStore(Cart $cart): void
    {
        abort_unless($cart->store_id === app('current_store')->id, Response::HTTP_NOT_FOUND);
    }
}
