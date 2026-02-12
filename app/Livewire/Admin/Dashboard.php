<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $store = app('current_store');
        $today = Carbon::today();

        $totalRevenue = Payment::query()
            ->where('status', PaymentStatus::Captured)
            ->whereHas('order', fn ($query) => $query->where('store_id', $store->id))
            ->sum('amount');

        $totalOrders = Order::query()->count();
        $totalCustomers = Customer::query()->count();
        $totalProducts = Product::query()->active()->count();

        $recentOrders = Order::query()
            ->with('customer')
            ->latest()
            ->limit(5)
            ->get();

        $ordersToday = Order::query()
            ->whereDate('created_at', $today)
            ->count();

        $revenueToday = Payment::query()
            ->where('status', PaymentStatus::Captured)
            ->whereHas('order', fn ($query) => $query->where('store_id', $store->id))
            ->whereDate('captured_at', $today)
            ->sum('amount');

        return view('livewire.admin.dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalCustomers' => $totalCustomers,
            'totalProducts' => $totalProducts,
            'recentOrders' => $recentOrders,
            'ordersToday' => $ordersToday,
            'revenueToday' => $revenueToday,
        ]);
    }

    public static function formatMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',').' EUR';
    }
}
