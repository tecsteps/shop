<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStoreAccess($store);

        $query = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('customer')
            ->withCount('lines');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('financial_status')) {
            $query->where('financial_status', $request->input('financial_status'));
        }

        if ($request->filled('fulfillment_status')) {
            $query->where('fulfillment_status', $request->input('fulfillment_status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'placed_at_desc');
        match ($sort) {
            'placed_at_asc' => $query->orderBy('placed_at', 'asc'),
            'total_desc' => $query->orderBy('total_amount', 'desc'),
            'total_asc' => $query->orderBy('total_amount', 'asc'),
            default => $query->orderBy('placed_at', 'desc'),
        };

        $perPage = min((int) $request->input('per_page', 25), 100);
        $orders = $query->paginate($perPage);

        return OrderListResource::collection($orders)
            ->response();
    }

    public function show(Store $store, Order $order): OrderResource|JsonResponse
    {
        $this->authorizeStoreAccess($store);

        if ($order->store_id !== $store->id) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return new OrderResource($order);
    }

    private function authorizeStoreAccess(Store $store): void
    {
        $user = auth()->user();

        if (! $user || ! $user->stores()->where('stores.id', $store->id)->exists()) {
            abort(403, 'You do not have access to this store.');
        }
    }
}
