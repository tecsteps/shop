<?php

namespace App\Livewire\Storefront\Account;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    use ResolvesStore;

    public function render()
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();

        abort_if($customer === null, 401);

        $orders = $customer->orders()->latest('placed_at')->latest('id')->take(5)->get();
        $orderCount = $customer->orders()->count();
        $addressCount = $customer->customerAddresses()->count();

        return view('livewire.storefront.account.dashboard', [
            'store' => $store,
            'customer' => $customer,
            'orders' => $orders,
            'orderCount' => $orderCount,
            'addressCount' => $addressCount,
        ])->layout('storefront.layouts.app', [
            'title' => 'My Account - '.$store->name,
            'metaDescription' => 'Your account at '.$store->name,
        ]);
    }
}
