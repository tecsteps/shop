<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public int $itemCount = 0;

    #[On('cart-updated')]
    public function cartUpdated(int $itemCount = 0): void
    {
        $this->itemCount = $itemCount;
        $this->open = true;
    }

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    #[On('close-cart-drawer')]
    public function closeDrawer(): void
    {
        $this->open = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.cart-drawer');
    }
}
