<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\ManagesCartLines;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    use ManagesCartLines, ResolvesStore;

    public bool $open = false;

    public function mount(): void
    {
        $this->syncCartVersion();
    }

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
        $this->syncCartVersion();
    }

    #[On('close-cart-drawer')]
    public function closeDrawer(): void
    {
        $this->open = false;
    }

    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
        $this->open = true;
        $this->syncCartVersion();
    }

    public function render()
    {
        $cart = $this->findCart();

        if ($this->cartVersion === null) {
            $this->cartVersion = $cart?->cart_version;
        }

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
            'lines' => $this->cartLinesData($cart),
            'totals' => $this->cartTotals($cart),
            'appliedCode' => $this->sessionDiscountCode(),
        ]);
    }
}
