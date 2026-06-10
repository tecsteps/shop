<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrderListResource;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * GET /api/admin/v1/stores/{storeId}/orders
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])],
            'financial_status' => ['nullable', Rule::in(['pending', 'paid', 'partially_refunded', 'refunded'])],
            'fulfillment_status' => ['nullable', Rule::in(['unfulfilled', 'partial', 'fulfilled'])],
            'customer_id' => ['nullable', 'integer'],
            'created_after' => ['nullable', 'date'],
            'created_before' => ['nullable', 'date'],
            'query' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['placed_at_desc', 'placed_at_asc', 'total_desc', 'total_asc'])],
        ]);

        [$sortColumn, $sortDirection] = match ($validated['sort'] ?? 'placed_at_desc') {
            'placed_at_asc' => ['placed_at', 'asc'],
            'total_desc' => ['total_amount', 'desc'],
            'total_asc' => ['total_amount', 'asc'],
            default => ['placed_at', 'desc'],
        };

        $orders = Order::query()
            ->with('customer')
            ->withCount('lines')
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['financial_status']), fn ($query) => $query->where('financial_status', $validated['financial_status']))
            ->when(isset($validated['fulfillment_status']), fn ($query) => $query->where('fulfillment_status', $validated['fulfillment_status']))
            ->when(isset($validated['customer_id']), fn ($query) => $query->where('customer_id', $validated['customer_id']))
            ->when(isset($validated['created_after']), fn ($query) => $query->where('placed_at', '>=', $validated['created_after']))
            ->when(isset($validated['created_before']), fn ($query) => $query->where('placed_at', '<=', $validated['created_before']))
            ->when(filled($validated['query'] ?? null), function ($query) use ($validated): void {
                $term = '%'.$validated['query'].'%';
                $query->where(fn ($inner) => $inner
                    ->where('order_number', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(perPage: (int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => OrderListResource::collection($orders->items())->resolve(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/v1/stores/{storeId}/orders/{orderId}
     */
    public function show(int $storeId, int $orderId): OrderResource
    {
        return new OrderResource(
            Order::query()
                ->with(['customer', 'lines', 'payments', 'fulfillments.lines', 'refunds'])
                ->findOrFail($orderId),
        );
    }
}
