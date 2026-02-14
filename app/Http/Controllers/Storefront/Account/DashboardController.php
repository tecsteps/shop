<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends AccountController
{
    public function index(Request $request): View
    {
        $customer = $this->currentCustomer($request);

        $recentOrders = Order::query()
            ->where('store_id', $this->currentStoreId($request))
            ->where('customer_id', $customer->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $addressCount = $customer->addresses()->count();

        return view('storefront.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $recentOrders,
            'addressCount' => $addressCount,
        ]);
    }
}
