<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\FulfillmentResource;
use App\Http\Resources\Admin\OrderListResource;
use App\Http\Resources\Admin\OrderResource;
use App\Http\Resources\Admin\RefundResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingRate;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin orders API (spec 02 §3.4) and CSV order export (spec 05 §11.6).
 */
class OrderController extends Controller
{
    /**
     * GET /api/admin/v1/stores/{storeId}/orders — list with filtering,
     * sorting, and pagination.
     */
    public function index(Request $request, int $storeId): AnonymousResourceCollection
    {
        $validated = $request->validate($this->filterRules());

        $query = $this->filteredQuery($validated)
            ->with('customer')
            ->withCount('lines');

        match ($validated['sort'] ?? 'placed_at_desc') {
            'placed_at_asc' => $query->orderBy('placed_at'),
            'total_desc' => $query->orderByDesc('total_amount'),
            'total_asc' => $query->orderBy('total_amount'),
            default => $query->orderByDesc('placed_at'),
        };

        return OrderListResource::collection(
            $query->paginate((int) ($validated['per_page'] ?? 25)),
        );
    }

    /**
     * GET /api/admin/v1/stores/{storeId}/orders/{orderId} — full detail.
     */
    public function show(Request $request, int $storeId, int $orderId): OrderResource
    {
        return new OrderResource(Order::query()->findOrFail($orderId));
    }

    /**
     * POST /api/admin/v1/stores/{storeId}/orders/{orderId}/fulfillments —
     * create a fulfillment and mark it shipped (spec 02 §3.4).
     */
    public function storeFulfillment(Request $request, int $storeId, int $orderId, FulfillmentService $fulfillments): JsonResponse
    {
        $validated = $request->validate([
            'tracking_company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.order_line_id' => ['required', 'integer'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'notify_customer' => ['sometimes', 'boolean'],
        ]);

        $order = Order::query()->findOrFail($orderId);

        abort_if(
            in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Fulfilled], true),
            409,
            'Order is not in a fulfillable state.',
        );

        $lines = collect($validated['line_items'])
            ->mapWithKeys(fn (array $line): array => [(int) $line['order_line_id'] => (int) $line['quantity']])
            ->all();

        $tracking = Arr::only($validated, ['tracking_company', 'tracking_number', 'tracking_url']);

        try {
            $fulfillment = $fulfillments->create($order, $lines, $tracking);
        } catch (FulfillmentGuardException $exception) {
            abort(409, $exception->getMessage());
        }

        $fulfillments->markAsShipped($fulfillment, $tracking);

        return (new FulfillmentResource($fulfillment->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/admin/v1/stores/{storeId}/orders/{orderId}/refunds —
     * create a refund against the order's captured payment (spec 02 §3.4).
     */
    public function storeRefund(Request $request, int $storeId, int $orderId, RefundService $refunds): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'line_items' => ['sometimes', 'array'],
            'line_items.*.order_line_id' => ['required', 'integer'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'notify_customer' => ['sometimes', 'boolean'],
        ]);

        $order = Order::query()->findOrFail($orderId);

        $payment = $order->payments()
            ->where('status', PaymentStatus::Captured)
            ->orderByDesc('id')
            ->first();

        abort_if($payment === null || $order->refundableAmount() <= 0, 409, 'Order cannot be refunded.');

        $refund = $refunds->create(
            $order,
            $payment,
            (int) $validated['amount'],
            $validated['reason'] ?? null,
        );

        return (new RefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/admin/v1/stores/{storeId}/orders/export — CSV of orders
     * matching the current filter criteria (spec 05 §11.6).
     */
    public function export(Request $request, int $storeId): Response
    {
        $validated = $request->validate($this->filterRules());

        $orders = $this->filteredQuery($validated)
            ->with(['customer', 'checkout', 'fulfillments'])
            ->orderByDesc('placed_at')
            ->get();

        $shippingRateNames = [];

        $rows = $orders->map(function (Order $order) use (&$shippingRateNames): array {
            $shippingRateId = $order->checkout?->shipping_method_id;

            if ($shippingRateId !== null && ! array_key_exists($shippingRateId, $shippingRateNames)) {
                $shippingRateNames[$shippingRateId] = ShippingRate::query()->whereKey($shippingRateId)->value('name');
            }

            $trackingNumber = $order->fulfillments
                ->sortByDesc('id')
                ->first(fn ($fulfillment): bool => $fulfillment->tracking_number !== null)
                ?->tracking_number;

            return [
                $order->order_number,
                $order->created_at?->toIso8601ZuluString(),
                $order->status->value,
                $order->financial_status->value,
                $order->fulfillment_status->value,
                $order->customer?->email ?? $order->email,
                $order->customer?->name,
                $order->subtotal_amount,
                $order->discount_amount,
                $order->shipping_amount,
                $order->tax_amount,
                $order->total_amount,
                $order->currency,
                $shippingRateNames[$shippingRateId] ?? null,
                $trackingNumber,
            ];
        });

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'order_number', 'created_at', 'status', 'financial_status', 'fulfillment_status',
            'customer_email', 'customer_name', 'subtotal_amount', 'discount_amount',
            'shipping_amount', 'tax_amount', 'total_amount', 'currency',
            'shipping_method', 'tracking_number',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Shared filter validation for list and export.
     *
     * @return array<string, mixed>
     */
    private function filterRules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'financial_status' => ['sometimes', Rule::enum(FinancialStatus::class)],
            'fulfillment_status' => ['sometimes', Rule::enum(FulfillmentOrderStatus::class)],
            'customer_id' => ['sometimes', 'integer'],
            'created_after' => ['sometimes', 'date'],
            'created_before' => ['sometimes', 'date'],
            'query' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in(['placed_at_desc', 'placed_at_asc', 'total_desc', 'total_asc'])],
        ];
    }

    /**
     * Build the filtered order query (tenant-scoped via the global scope).
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Order>
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Order::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['financial_status'])) {
            $query->where('financial_status', $filters['financial_status']);
        }

        if (isset($filters['fulfillment_status'])) {
            $query->where('fulfillment_status', $filters['fulfillment_status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (isset($filters['created_after'])) {
            $query->where('placed_at', '>=', $filters['created_after']);
        }

        if (isset($filters['created_before'])) {
            $query->where('placed_at', '<=', $filters['created_before']);
        }

        if (($filters['query'] ?? '') !== '') {
            $term = '%'.$filters['query'].'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('order_number', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereHas('customer', fn (Builder $customers) => $customers->where('email', 'like', $term));
            });
        }

        return $query;
    }
}
