<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Exceptions\InvalidRefundOperationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\CreateOrderRefundRequest;
use App\Http\Resources\Admin\V1\RefundResource;
use App\Models\Order;
use App\Models\Store;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRefundController extends Controller
{
    public function store(CreateOrderRefundRequest $request, Store $store, Order $order, RefundService $refunds): RefundResource|JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessOrderBelongsToStore($order, $store);

        $payload = [
            'lines' => $request->lineQuantities(),
            'reason' => $request->validated('reason'),
            'restock' => (bool) $request->validated('restock', false),
        ];

        if ($request->validated('amount') !== null) {
            $payload['amount'] = $request->validated('amount');
        }

        try {
            $refund = $refunds->process($order, $payload);

            return RefundResource::make($refund)
                ->response()
                ->setStatusCode(201);
        } catch (InvalidRefundOperationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);

        app()->instance('current_store', $store);
    }

    private function abortUnlessOrderBelongsToStore(Order $order, Store $store): void
    {
        abort_unless((int) $order->store_id === $store->getKey(), 404);
    }
}
