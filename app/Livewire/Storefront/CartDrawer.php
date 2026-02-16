<?php

namespace App\Livewire\Storefront;

use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    /** @var array<string, string> */
    protected $listeners = [
        'cart-updated' => 'openDrawer',
        'open-cart-drawer' => 'openDrawer',
        'close-cart-drawer' => 'closeDrawer',
    ];

    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function closeDrawer(): void
    {
        $this->open = false;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart-drawer');
    }
}
