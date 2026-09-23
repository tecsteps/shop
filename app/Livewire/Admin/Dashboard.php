<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Product;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): mixed
    {
        abort_unless(auth()->user()->can('view-admin'), 403);
        $storeId = app('current_store')->id;
        $orders = Order::query()->where('store_id', $storeId);
        $todayOrders = (clone $orders)->whereDate('created_at', today());

        return view('livewire.admin.dashboard', [
            'ordersToday' => $todayOrders->count(),
            'revenueToday' => (int) (clone $todayOrders)->whereIn('financial_status', ['paid', 'partially_refunded'])->sum('total_amount'),
            'openOrders' => (clone $orders)->whereIn('status', ['pending', 'paid'])->count(),
            'productsCount' => Product::query()->where('store_id', $storeId)->count(),
            'recentOrders' => (clone $orders)->with('customer')->latest('placed_at')->limit(8)->get(),
            'visits' => AnalyticsEvent::query()->where('store_id', $storeId)->where('type', 'page_view')->whereDate('created_at', today())->count(),
        ])->layout('layouts.admin');
    }
}
