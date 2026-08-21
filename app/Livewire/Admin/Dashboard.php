<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsDaily;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Livewire\Component;

class Dashboard extends Component
{
    public string $range = '30';

    public function render(): mixed
    {
        $days = in_array($this->range, ['7', '30', '90'], true) ? (int) $this->range : 30;
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();
        $orders = Order::query()->with('customer')->whereBetween('placed_at', [$from, $to])->latest('placed_at')->take(10)->get();
        $sales = (int) Order::query()->where('financial_status', 'paid')->whereBetween('placed_at', [$from, $to])->sum('total_amount');
        $orderCount = (int) Order::query()->whereBetween('placed_at', [$from, $to])->count();
        $analytics = AnalyticsDaily::query()->whereBetween('date', [$from->toDateString(), $to->toDateString()])->orderBy('date')->get();
        $topProducts = OrderLine::query()->whereHas('order', fn ($query) => $query->where('financial_status', 'paid')->whereBetween('placed_at', [$from, $to]))->select('product_id', 'product_title')->selectRaw('SUM(quantity) AS units, SUM(line_total_amount) AS revenue')->groupBy('product_id', 'product_title')->orderByDesc('revenue')->limit(5)->get();

        return view('livewire.admin.dashboard', ['orders' => $orders, 'sales' => $sales, 'orderCount' => $orderCount, 'productCount' => Product::query()->count(), 'analytics' => $analytics, 'topProducts' => $topProducts, 'visitors' => (int) $analytics->sum('visits_count'), 'addToCart' => (int) $analytics->sum('add_to_cart_count'), 'checkoutStarted' => (int) $analytics->sum('checkout_started_count')])->layout('layouts.admin');
    }
}
