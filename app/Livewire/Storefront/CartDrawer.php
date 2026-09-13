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
        $this->syncCartVersion();

        if ($this->open || $this->isCartPageRequest()) {
            return;
        }

        $this->open = true;
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

    /**
     * The drawer must not auto-open over the dedicated cart page: its overlay
     * would intercept the page's own quantity and checkout controls.
     */
    protected function isCartPageRequest(): bool
    {
        $refererPath = parse_url((string) request()->headers->get('referer'), PHP_URL_PATH);

        if (! is_string($refererPath) || $refererPath === '') {
            return false;
        }

        return $refererPath === parse_url(route('storefront.cart'), PHP_URL_PATH);
    }
}
