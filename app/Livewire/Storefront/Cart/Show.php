<?php

namespace App\Livewire\Storefront\Cart;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public function render(): \Illuminate\View\View
    {
        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');

        return view('livewire.storefront.cart.show')
            ->title("Cart - {$storeName}");
    }
}
