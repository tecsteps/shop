<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends AccountController
{
    public function index(Request $request): View
    {
        $customer = $this->currentCustomer($request);

        $orders = Order::query()
            ->where('store_id', $this->currentStoreId($request))
            ->where('customer_id', $customer->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('storefront.account.orders.index', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, string $orderNumber): View
    {
        $customer = $this->currentCustomer($request);

        $order = Order::query()
            ->where('store_id', $this->currentStoreId($request))
            ->where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with([
                'lines.variant.product',
                'fulfillments',
            ])
            ->firstOrFail();

        return view('storefront.account.orders.show', [
            'customer' => $customer,
            'order' => $order,
        ]);
    }
}
