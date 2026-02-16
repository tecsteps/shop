<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Home extends Component
{
    public function render(): mixed
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return view('livewire.storefront.home', [
            'storeName' => $store?->name ?? config('app.name'),
        ]);
    }
}
