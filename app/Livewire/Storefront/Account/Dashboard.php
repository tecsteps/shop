<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Order;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): mixed
    {
        $customer = auth('customer')->user();

        return view('storefront.account.index', [
            'customer' => $customer,
            'orders' => Order::query()->where('customer_id', $customer->id)->latest('placed_at')->limit(5)->get(),
            'addresses' => $customer->addresses()->orderByDesc('is_default')->get(),
        ])->layout('storefront.layouts.app');
    }
}
