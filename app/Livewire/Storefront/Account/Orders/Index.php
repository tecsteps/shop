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

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.account.orders.index', [
            'orders' => $this->orders(),
        ])->layout('layouts.storefront', [
            'title' => 'Account',
        ]);
    }

    /**
     * @return Collection<int, Order>
     */
    private function orders(): Collection
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($customer instanceof Customer, 403);

        return Order::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->where('customer_id', $customer->getKey())
            ->latest('placed_at')
            ->limit(20)
            ->get();
    }
}
