<?php

namespace App\Livewire\Storefront;

use App\Exceptions\InsufficientInventoryException;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuickAdd extends Component
{
    use ResolvesStore;

    public int $variantId;

    public ?string $message = null;

    public function mount(int $variantId): void
    {
        $this->variantId = $variantId;
    }

    public function add(): void
    {
        $store = $this->store();
        $customer = Auth::guard('customer')->user();
        $carts = app(CartService::class);

        try {
            $cart = $carts->getOrCreateForSession($store, $customer);
            $carts->addLine($cart, $this->variantId, 1);
        } catch (InsufficientInventoryException) {
            $this->message = 'This product is currently out of stock';

            return;
        } catch (ValidationException $exception) {
            $this->message = collect($exception->errors())->flatten()->first() ?? 'Unable to add to cart.';

            return;
        }

        $this->message = null;
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.storefront.quick-add');
    }
}
