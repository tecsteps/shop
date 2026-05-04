<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public int $storeId;

    #[Locked]
    public bool $isDashboard = false;

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
        $this->isDashboard = request()->routeIs('account.dashboard');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.orders.index', [
            'customer' => $this->customer(),
            'isDashboard' => $this->isDashboard,
            'orders' => $this->orders($this->isDashboard),
        ])->layout('layouts.storefront', [
            'title' => 'Account',
        ]);
    }

    /**
     * @return Collection<int, Order>
     */
    private function orders(bool $isDashboard): Collection
    {
        return Order::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->where('customer_id', $this->customer()->getKey())
            ->latest('placed_at')
            ->latest('id')
            ->limit($isDashboard ? 5 : 20)
            ->get();
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($customer instanceof Customer, 403);

        return Customer::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereKey($customer->getKey())
            ->firstOrFail();
    }
}
