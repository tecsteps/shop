<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('livewire.storefront.account.orders.index', [
            'orders' => Order::query()
                ->where('customer_id', $this->customer()->id)
                ->latest('placed_at')
                ->paginate(10),
        ])->layout('storefront.layouts.app', [
            'title' => 'Order history',
        ]);
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }
}
