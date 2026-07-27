<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /**
     * Render the paginated order history of the authenticated customer
     * (spec 04 §10.4).
     */
    public function render(): View
    {
        $orders = Auth::guard('customer')->user()
            ->orders()
            ->latest('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ])
            ->layout('storefront.layouts.app')
            ->title('Order history');
    }
}
