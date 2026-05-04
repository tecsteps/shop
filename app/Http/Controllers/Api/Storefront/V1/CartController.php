<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\V1\StoreCartRequest;
use App\Http\Resources\Storefront\V1\CartResource;
use App\Models\Cart;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function store(StoreCartRequest $request, CartService $carts): JsonResponse
    {
        $request->validated();

        return CartResource::make($this->loadCart($carts->create($this->currentStore())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Cart $cart): CartResource
    {
        return CartResource::make($this->loadCart($cart));
    }

    private function currentStore(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function loadCart(Cart $cart): Cart
    {
        return $cart->load([
            'lines.variant.product',
            'lines.variant.optionValues.option',
        ]);
    }
}
