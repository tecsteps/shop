<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CartStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartController extends Controller
{
    public function __construct(
        protected CartService $service,
        protected PricingEngine $pricing,
        protected DiscountService $discounts,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currency' => 'nullable|string|size:3',
        ]);

        $store = app('current_store');
        $cart = $this->service->create($store);
        if (! empty($data['currency'])) {
            $cart->currency = strtoupper($data['currency']);
            $cart->save();
        }

        return CartResource::fromCart($cart, $this->pricing)->response()->setStatusCode(201);
    }

    public function show(int $cartId): CartResource
    {
        $cart = $this->findCart($cartId);

        return CartResource::fromCart($cart, $this->pricing);
    }

    public function addLine(Request $request, int $cartId): CartResource
    {
        $data = $request->validate([
            'variant_id' => 'required|integer',
            'quantity' => 'required|integer|min:1|max:9999',
        ]);

        $cart = $this->findCart($cartId);

        try {
            $this->service->addLine($cart, (int) $data['variant_id'], (int) $data['quantity']);
        } catch (InsufficientInventoryException $e) {
            throw ValidationException::withMessages(['variant_id' => $e->getMessage()]);
        }

        return CartResource::fromCart($cart->fresh(), $this->pricing);
    }

    public function updateLine(Request $request, int $cartId, int $lineId): CartResource
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:9999',
            'cart_version' => 'required|integer',
        ]);

        $cart = $this->findCart($cartId);

        try {
            $this->service->updateLineQuantity($cart, $lineId, (int) $data['quantity'], (int) $data['cart_version']);
        } catch (InsufficientInventoryException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return CartResource::fromCart($cart->fresh(), $this->pricing);
    }

    public function removeLine(Request $request, int $cartId, int $lineId): CartResource
    {
        $data = $request->validate([
            'cart_version' => 'required|integer',
        ]);

        $cart = $this->findCart($cartId);

        $this->service->removeLine($cart, $lineId, (int) $data['cart_version']);

        return CartResource::fromCart($cart->fresh(), $this->pricing);
    }

    protected function findCart(int $cartId): Cart
    {
        $store = app('current_store');

        $cart = Cart::query()
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active)
            ->with('lines.variant.product')
            ->find($cartId);

        if (! $cart) {
            throw new NotFoundHttpException('Cart not found');
        }

        return $cart;
    }
}
