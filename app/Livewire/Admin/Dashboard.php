<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Product;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): mixed
    {
        $orders = Order::query()->with('customer')->latest('placed_at')->take(10)->get();
        $sales = (int) Order::query()->where('financial_status', 'paid')->sum('total_amount');
        $orderCount = (int) Order::query()->count();

        return view('livewire.admin.dashboard', ['orders' => $orders, 'sales' => $sales, 'orderCount' => $orderCount, 'productCount' => Product::query()->count()])->layout('layouts.admin');
    }
}
