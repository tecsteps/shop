<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFulfillmentRequest;
use App\Http\Requests\CreateRefundRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Store;
use App\Services\FulfillmentService;
use App\Services\PaymentService;
use App\Services\RefundService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(private readonly FulfillmentService $fulfillmentService, private readonly RefundService $refundService, private readonly PaymentService $paymentService) {}

    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->ensureStore($store);
        $orders = Order::query()->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')))->when($request->filled('search'), fn (Builder $query): Builder => $query->where(fn (Builder $search): Builder => $search->where('order_number', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))->latest('placed_at')->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Store $store, Order $order): OrderResource
    {
        $this->ensureOrder($store, $order);

        return new OrderResource($order->load(['lines', 'payments', 'refunds', 'fulfillments.lines', 'customer']));
    }

    public function fulfill(CreateFulfillmentRequest $request, Store $store, Order $order): JsonResponse
    {
        $this->ensureOrder($store, $order);
        $data = $request->validated();
        $fulfillment = $this->fulfillmentService->create($order, $data['lines'], $data);

        return response()->json(['data' => $fulfillment], Response::HTTP_CREATED);
    }

    public function refund(CreateRefundRequest $request, Store $store, Order $order): JsonResponse
    {
        $this->ensureOrder($store, $order);
        $data = $request->validated();
        $payment = $order->payments()->findOrFail($data['payment_id']);
        $refund = $this->refundService->create($order, $payment, $data['amount'], $data['reason'] ?? null, $data['restock'] ?? false);

        return response()->json(['data' => $refund], Response::HTTP_CREATED);
    }

    public function confirmPayment(Store $store, Order $order): OrderResource
    {
        $this->ensureOrder($store, $order);
        $this->paymentService->confirmBankTransfer($order);

        return new OrderResource($order->refresh()->load(['lines', 'payments', 'fulfillments.lines']));
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }

    private function ensureOrder(Store $store, Order $order): void
    {
        $this->ensureStore($store);
        abort_unless($order->store_id === $store->id, Response::HTTP_NOT_FOUND);
    }
}
