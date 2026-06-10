<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RefundResource;
use App\Models\Order;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRefundController extends Controller
{
    public function __construct(protected RefundService $refundService) {}

    /**
     * POST /api/admin/v1/stores/{storeId}/orders/{orderId}/refunds
     */
    public function store(Request $request, int $storeId, int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.order_line_id' => ['required', 'integer'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'notify_customer' => ['nullable', 'boolean'],
            'restock' => ['nullable', 'boolean'],
        ]);

        $payment = $order->payments()
            ->whereIn('status', [PaymentStatus::Captured, PaymentStatus::Refunded])
            ->latest('id')
            ->first();

        if ($payment === null || $order->remainingRefundableAmount() < 1) {
            return response()->json([
                'message' => __('This order cannot be refunded.'),
            ], 409);
        }

        $refund = $this->refundService->create(
            $order,
            $payment,
            $validated['amount'],
            $validated['reason'] ?? null,
            (bool) ($validated['restock'] ?? false),
        );

        return (new RefundResource($refund))->response()->setStatusCode(201);
    }
}
