<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends AdminController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
            'currency' => $this->currentStore()->default_currency,
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load('addresses');

        $orders = $customer->orders()
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'currency' => $this->currentStore()->default_currency,
        ]);
    }
}
