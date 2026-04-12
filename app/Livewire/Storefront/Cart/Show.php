<?php

namespace App\Livewire\Storefront\Cart;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Services\CartService;
use App\Support\CartSession;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use EnsuresStore;

    public string $discountCode = '';

    public function mount(): void
    {
        $this->ensureCurrentStore();
    }

    public function updateQty(int $lineId, int $qty): void
    {
        $cart = CartSession::current();
        if ($cart === null) {
            return;
        }

        $qty = max(1, $qty);

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $qty);
        } catch (RuntimeException $exception) {
            $this->addError('cart', $exception->getMessage());
        }

        $this->dispatch('cart-updated');
    }

    public function removeLine(int $lineId): void
    {
        $cart = CartSession::current();
        if ($cart === null) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        session(['discount_code' => $this->discountCode]);
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $this->ensureCurrentStore();

        $cart = CartSession::current();
        $cart?->load('lines.variant.product');

        $subtotal = 0;
        if ($cart !== null) {
            foreach ($cart->lines as $line) {
                $subtotal += (int) $line->line_subtotal_amount;
            }
        }

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'subtotal' => $subtotal,
        ]);
    }
}
