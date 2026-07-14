<?php

namespace App\Livewire\Storefront\Cart;

use App\Livewire\Storefront\Concerns\ManagesCart;
use App\Livewire\Storefront\StorefrontComponent;
use Illuminate\View\View;

class Show extends StorefrontComponent
{
    use ManagesCart;

    public function mount(): void
    {
        $this->initializeCartDiscount();
    }

    public function render(): View
    {
        return $this->storefront(
            view('storefront.cart.show'),
            'Your Cart - '.$this->currentStore()->name,
            'Review the items in your shopping cart.',
        );
    }
}
