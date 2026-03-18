<?php

namespace App\Livewire\Storefront\Account\Orders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Order History')]
class Index extends Component
{
    use WithPagination;

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Auth::guard('customer')->user()
            ->orders()
            ->latest('placed_at')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.storefront.account.orders.index')
            ->layout('storefront.layouts.app', ['title' => 'Order History']);
    }
}
