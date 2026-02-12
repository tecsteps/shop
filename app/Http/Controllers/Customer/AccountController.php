<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Storefront\StorefrontController;
use App\Models\Order;
use Illuminate\Http\Request;

class AccountController extends StorefrontController
{
    public function dashboard(Request $request)
    {
        $customer = $this->customer();
        abort_if(! $customer, 403);

        $recentOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->latest('placed_at')
            ->limit(5)
            ->get();

        return view('customer.dashboard', $this->storefrontViewData($request, [
            'recentOrders' => $recentOrders,
            'title' => 'Account Dashboard',
            'metaDescription' => 'Manage your orders and addresses.',
        ]));
    }
}
