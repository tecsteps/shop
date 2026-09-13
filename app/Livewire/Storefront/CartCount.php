<?php

namespace App\Livewire\Storefront;

use App\Enums\CartStatus;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    use ResolvesStore;

    #[On('cart-updated')]
    public function refreshCount(): void {}

    public function getCountProperty(): int
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();
        $query = Cart::query()->where('store_id', $store->getKey())->where('status', CartStatus::Active);

        $cartId = session()->get('cart_id');

        if ($cartId !== null) {
            $cart = (clone $query)->whereKey($cartId)->first();

            if ($cart !== null) {
                return (int) $cart->cartLines()->sum('quantity');
            }
        }

        if ($customer !== null) {
            $cart = (clone $query)->where('customer_id', $customer->getKey())->first();

            if ($cart !== null) {
                return (int) $cart->cartLines()->sum('quantity');
            }
        }

        return 0;
    }

    public function render()
    {
        return view('livewire.storefront.cart-count', ['count' => $this->count]);
    }
}
