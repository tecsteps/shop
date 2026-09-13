<?php

namespace App\Livewire\Storefront\Cart;

use App\Livewire\Storefront\Concerns\ManagesCartLines;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use Livewire\Component;

class Show extends Component
{
    use ManagesCartLines, ResolvesStore;

    public function mount(): void
    {
        $this->syncCartVersion();
    }

    public function render()
    {
        $store = $this->store();
        $cart = $this->findCart();

        if ($this->cartVersion === null) {
            $this->cartVersion = $cart?->cart_version;
        }

        return view('livewire.storefront.cart.show', [
            'store' => $store,
            'cart' => $cart,
            'lines' => $this->cartLinesData($cart),
            'totals' => $this->cartTotals($cart),
            'appliedCode' => $this->sessionDiscountCode(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Your Cart - '.$store->name,
            'metaDescription' => 'Review your cart at '.$store->name,
        ]);
    }
}
