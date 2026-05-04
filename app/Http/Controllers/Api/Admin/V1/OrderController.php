<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\OrderResource;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])],
            'financial_status' => ['nullable', Rule::in(['pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided'])],
            'fulfillment_status' => ['nullable', Rule::in(['unfulfilled', 'partial', 'fulfilled'])],
            'query' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::withoutGlobalScopes()
            ->with('customer')
            ->withCount('lines')
            ->where('store_id', $store->getKey())
            ->when(data_get($validated, 'status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(data_get($validated, 'financial_status'), fn (Builder $query, string $status) => $query->where('financial_status', $status))
            ->when(data_get($validated, 'fulfillment_status'), fn (Builder $query, string $status) => $query->where('fulfillment_status', $status))
            ->when(data_get($validated, 'query'), function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('order_number', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('customer', fn (Builder $query) => $query->where('name', 'like', $like));
                });
            })
            ->latest('placed_at')
            ->latest('id')
            ->paginate((int) data_get($validated, 'per_page', 25));

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Store $store, Order $order): OrderResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessOrderBelongsToStore($order, $store);

        return OrderResource::make($this->loadOrder($order));
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

    private function loadOrder(Order $order): Order
    {
        return $order->load([
            'customer',
            'lines',
            'payments',
            'refunds',
            'fulfillments.lines',
        ])->loadCount('lines');
    }
}
