<?php

namespace App\Livewire\Storefront;

use App\Exceptions\InsufficientInventoryException;
use App\Livewire\Storefront\Concerns\InteractsWithCartDiscount;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Cart;
use App\Models\CartLine;
use App\Services\CartService;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    use InteractsWithCartDiscount, InteractsWithStore;

    public bool $open = false;

    /**
     * Refresh the cart and open the drawer. Triggered globally whenever the
     * cart changes; other components may pass open: false to update the
     * drawer without opening it (e.g. from the full cart page).
     */
    #[On('cart-updated')]
    public function refreshCart(bool $open = true): void
    {
        $this->open = $open;
    }

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function closeDrawer(): void
    {
        $this->open = false;
    }

    public function incrementLine(int $lineId): void
    {
        $line = $this->cart->lines()->findOrFail($lineId);

        $this->updateQuantity($line, $line->quantity + 1);
    }

    public function decrementLine(int $lineId): void
    {
        $line = $this->cart->lines()->findOrFail($lineId);

        $this->updateQuantity($line, $line->quantity - 1);
    }

    public function removeLine(int $lineId): void
    {
        app(CartService::class)->removeLine($this->cart, $lineId);

        $this->afterCartChange();
    }

    private function updateQuantity(CartLine $line, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeLine($line->id);

            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($this->cart, $line->id, $quantity);
        } catch (InsufficientInventoryException) {
            $this->dispatch('storefront-toast', type: 'error', message: 'Not enough stock available for that quantity.');
        }

        $this->afterCartChange();
    }

    private function afterCartChange(): void
    {
        $cart = $this->cart->fresh()->load('lines');

        $this->dispatch('cart-updated', cartId: $cart->id, itemCount: (int) $cart->lines->sum('quantity'));
    }

    #[Computed]
    public function cart(): Cart
    {
        return $this->sessionCart();
    }

    #[Computed]
    public function lines(): SupportCollection
    {
        return $this->cart->lines()
            ->with([
                'variant.product.media',
                'variant.optionValues.option',
            ])
            ->get()
            ->sortBy(fn ($line) => $line->id);
    }

    #[Computed]
    public function subtotal(): int
    {
        return $this->lines->sum(fn ($line) => $line->unit_price_amount * $line->quantity);
    }

    #[Computed]
    public function total(): int
    {
        return $this->subtotal - ($this->cartDiscount['amount'] ?? 0);
    }

    #[Computed]
    public function currency(): string
    {
        return $this->cart->currency ?? $this->store()->default_currency;
    }
}
