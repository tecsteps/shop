<?php

namespace App\Livewire\Admin;

use App\Enums\FinancialStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        $orders = Order::query()->orderByDesc('placed_at')->limit(5)->get();
        $kpis = [
            'orders_today' => Order::query()->whereDate('placed_at', today())->count(),
            'revenue_30d' => (int) Order::query()
                ->where('financial_status', FinancialStatus::Paid->value)
                ->where('placed_at', '>=', now()->subDays(30))
                ->sum('total_amount'),
            'products_active' => Product::query()->where('status', 'active')->count(),
            'customers_total' => Customer::query()->count(),
        ];

        return view('livewire.admin.dashboard', compact('orders', 'kpis'))->title('Dashboard');
    }
}
