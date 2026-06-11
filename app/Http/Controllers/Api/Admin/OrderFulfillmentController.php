<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\FulfillmentGuardException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\FulfillmentResource;
use App\Models\Order;
use App\Services\FulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderFulfillmentController extends Controller
{
    public function __construct(protected FulfillmentService $fulfillmentService) {}

    /**
     * POST /api/admin/v1/stores/{storeId}/orders/{orderId}/fulfillments
     *
     * Creates a fulfillment and marks it shipped (spec 02 section 3.4:
     * "Create a fulfillment (mark items as shipped)").
     */
    public function store(Request $request, int $storeId, int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);

        $validated = $request->validate([
            'tracking_company' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.order_line_id' => ['required', 'integer'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'notify_customer' => ['nullable', 'boolean'],
        ]);

        $lines = collect($validated['line_items'])
            ->mapWithKeys(fn (array $line): array => [(int) $line['order_line_id'] => (int) $line['quantity']])
            ->all();

        $tracking = [
            'tracking_company' => $validated['tracking_company'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
        ];

        try {
            $fulfillment = $this->fulfillmentService->create($order, $lines, $tracking);
        } catch (FulfillmentGuardException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        $this->fulfillmentService->markAsShipped($fulfillment, $tracking);

        return (new FulfillmentResource($fulfillment->refresh()->load('lines')))
            ->response()
            ->setStatusCode(201);
    }
}
