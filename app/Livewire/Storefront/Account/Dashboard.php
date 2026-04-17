<?php

namespace App\Livewire\Storefront\Account;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Dashboard extends Component
{
    public function render()
    {
        $customer = auth('customer')->user();
        $orders = $customer->orders()->latest('placed_at')->limit(10)->get();

        return view('livewire.storefront.account.dashboard', compact('customer', 'orders'))
            ->title('Account');
    }
}
