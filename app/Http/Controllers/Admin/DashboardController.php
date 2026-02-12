<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancialStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends AdminController
{
    public function __invoke(): View
    {
        $currency = $this->currentStore()->default_currency;

        $totalSales = Order::query()
            ->whereIn('financial_status', [
                FinancialStatus::Paid->value,
                FinancialStatus::PartiallyRefunded->value,
                FinancialStatus::Refunded->value,
            ])
            ->sum('total_amount');

        $ordersCount = Order::query()->count();
        $customersCount = Customer::query()->count();
        $productsCount = Product::query()->count();

        $recentOrders = Order::query()
            ->with('customer')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $lowStockCount = InventoryItem::query()
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= 5')
            ->count();

        return view('admin.dashboard', [
            'currency' => $currency,
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'customersCount' => $customersCount,
            'productsCount' => $productsCount,
            'lowStockCount' => $lowStockCount,
            'recentOrders' => $recentOrders,
        ]);
    }
}
