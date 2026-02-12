<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Storefront\StorefrontController;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends StorefrontController
{
    public function index(Request $request)
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->latest('placed_at')
            ->paginate(12)
            ->withQueryString();

        return view('customer.orders.index', $this->storefrontViewData($request, [
            'orders' => $orders,
            'title' => 'Order History',
            'metaDescription' => 'Review all your past orders.',
        ]));
    }

    public function show(Request $request, string $orderNumber)
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with(['lines', 'payments', 'fulfillments'])
            ->firstOrFail();

        return view('customer.orders.show', $this->storefrontViewData($request, [
            'order' => $order,
            'title' => 'Order '.$order->order_number,
            'metaDescription' => 'Order details and fulfillment status.',
        ]));
    }
}
