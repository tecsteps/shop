<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public int $storeId;

    public function mount(): void
    {
        $this->storeId = $this->store()->getKey();
    }

    #[On('cart-updated')]
    public function refreshCart(): void {}

    public function increaseQuantity(int $lineId): void
    {
        $line = $this->cartLine($lineId);

        if (! $line instanceof CartLine) {
            return;
        }

        app(CartService::class)->updateLineQuantity($line->cart, $line->getKey(), $line->quantity + 1);
        $this->dispatch('cart-updated');
    }

    public function decreaseQuantity(int $lineId): void
    {
        $line = $this->cartLine($lineId);

        if (! $line instanceof CartLine) {
            return;
        }

        app(CartService::class)->updateLineQuantity($line->cart, $line->getKey(), $line->quantity - 1);
        $this->dispatch('cart-updated');
    }

    public function removeLine(int $lineId): void
    {
        $line = $this->cartLine($lineId);

        if (! $line instanceof CartLine) {
            return;
        }

        app(CartService::class)->removeLine($line->cart, $line->getKey());
        $this->dispatch('cart-updated');
    }

    public function checkout(): void
    {
        if ($this->lineCount() === 0) {
            return;
        }

        $this->redirectRoute('checkout.show', navigate: true);
    }

    public function store(): Store
    {
        if (isset($this->storeId)) {
            $store = Store::query()->findOrFail($this->storeId);
            app()->instance('current_store', $store);

            return $store;
        }

        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    public function cart(): ?Cart
    {
        $cart = app(CartService::class)->currentForSession($this->store(), $this->customer());

        return $cart?->load([
            'lines.variant.product',
            'lines.variant.optionValues.option',
        ]);
    }

    /**
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        return $this->cart()?->lines->sortBy('id')->values() ?? collect();
    }

    public function lineCount(): int
    {
        return $this->lines()->sum('quantity');
    }

    public function subtotal(): int
    {
        return $this->lines()->sum('line_subtotal_amount');
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart.show', [
            'cart' => $this->cart(),
            'lines' => $this->lines(),
            'lineCount' => $this->lineCount(),
            'subtotal' => $this->subtotal(),
        ])->layout('layouts.storefront', [
            'title' => 'Cart',
        ]);
    }

    private function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer ? $customer : null;
    }

    private function cartLine(int $lineId): ?CartLine
    {
        return $this->lines()->firstWhere('id', $lineId);
    }
}
