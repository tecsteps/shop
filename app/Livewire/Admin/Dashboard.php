<?php

namespace App\Livewire\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $orders = Order::query();
        $orderCount = (clone $orders)->count();
        $revenue = (clone $orders)->whereIn('financial_status', ['paid', 'partially_refunded'])->sum('total_amount');

        return view('livewire.admin.dashboard', [
            'revenue' => $revenue,
            'orderCount' => $orderCount,
            'averageOrderValue' => $orderCount > 0 ? intdiv((int) $revenue, $orderCount) : 0,
            'customerCount' => Customer::query()->count(),
            'productCount' => Product::query()->count(),
            'recentOrders' => Order::query()->with('customer')->latest('placed_at')->limit(5)->get(),
        ]);
    }
}
