<?php

namespace App\Livewire\Storefront;

use Illuminate\View\View;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $isOpen = false;

    protected $listeners = ['cart-updated' => 'openDrawer'];

    public function openDrawer(): void
    {
        $this->isOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->isOpen = false;
    }

    public function render(): View
    {
        return view('livewire.storefront.cart-drawer');
    }
}
