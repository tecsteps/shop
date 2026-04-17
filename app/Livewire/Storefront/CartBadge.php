<?php

namespace App\Livewire\Storefront;

use App\Services\Cart\CartSession;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    public int $count = 0;

    public function mount(CartSession $cartSession): void
    {
        $this->count = $cartSession->lineCount();
    }

    #[On('cart-updated')]
    public function refresh(CartSession $cartSession): void
    {
        $this->count = $cartSession->lineCount();
    }

    public function render()
    {
        return view('livewire.storefront.cart-badge');
    }
}
