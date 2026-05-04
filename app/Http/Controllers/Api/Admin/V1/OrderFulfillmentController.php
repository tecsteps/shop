<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Exceptions\InvalidFulfillmentOperationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\CreateOrderFulfillmentRequest;
use App\Http\Resources\Admin\V1\FulfillmentResource;
use App\Models\Order;
use App\Models\Store;
use App\Services\FulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderFulfillmentController extends Controller
{
    public function store(CreateOrderFulfillmentRequest $request, Store $store, Order $order, FulfillmentService $fulfillments): FulfillmentResource|JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessOrderBelongsToStore($order, $store);
        abort_unless($request->user()?->can('createFulfillment', $order), 403);

        try {
            $fulfillment = $fulfillments->create($order, $request->lineQuantities(), [
                'tracking_company' => $request->validated('tracking_company'),
                'tracking_number' => $request->validated('tracking_number'),
                'tracking_url' => $request->validated('tracking_url'),
            ]);

            return FulfillmentResource::make($fulfillment->load('lines'))
                ->response()
                ->setStatusCode(201);
        } catch (InvalidFulfillmentOperationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('sanctum_personal_access_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessOrderBelongsToStore(Order $order, Store $store): void
    {
        abort_unless((int) $order->store_id === $store->getKey(), 404);
    }
}
