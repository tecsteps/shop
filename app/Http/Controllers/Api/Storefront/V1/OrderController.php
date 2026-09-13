<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Storefront\StorefrontOrderResource;
use App\Models\Order;
use App\Support\OrderAccessToken;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected CurrentStore $currentStore) {}

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::query()
            ->where('store_id', $this->currentStore->id())
            ->where('order_number', $orderNumber)
            ->with([
                'orderLines.variant.optionValues',
                'fulfillments.fulfillmentLines',
            ])
            ->first();

        if ($order === null) {
            abort(404);
        }

        if (! OrderAccessToken::verify($order, $request->query('token'))) {
            return response()->json([
                'message' => 'Invalid or missing access token.',
            ], 401);
        }

        return response()->json((new StorefrontOrderResource($order))->resolve());
    }
}
