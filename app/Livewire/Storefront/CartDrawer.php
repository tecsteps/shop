<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public ?int $cartId = null;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public int $subtotal = 0;

    public int $itemCount = 0;

    public function mount(): void
    {
        $this->loadCart();
    }

    #[On('cart-updated')]
    public function loadCart(): void
    {
        $this->cartId = session('cart_id');

        if (! $this->cartId) {
            $this->lines = [];
            $this->subtotal = 0;
            $this->itemCount = 0;

            return;
        }

        $cart = Cart::query()
            ->withoutGlobalScopes()
            ->where('id', $this->cartId)
            ->with('lines.variant.product')
            ->first();

        if (! $cart) {
            $this->lines = [];
            $this->subtotal = 0;
            $this->itemCount = 0;

            return;
        }

        $this->lines = $cart->lines->map(function ($line) {
            return [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant?->product?->title,
                'variant_title' => $line->variant?->title,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ];
        })->toArray();

        $this->subtotal = $cart->lines->sum('line_total_amount');
        $this->itemCount = $cart->lines->sum('quantity');
    }

    #[On('add-to-cart')]
    public function addToCart(int $variantId, int $quantity = 1): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);

        try {
            $cartService->addLine($cart, $variantId, $quantity);
            $this->loadCart();
            $this->open = true;
        } catch (\InvalidArgumentException $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        if (! $this->cartId) {
            return;
        }

        $cart = Cart::query()->withoutGlobalScopes()->find($this->cartId);

        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        try {
            if ($quantity <= 0) {
                $cartService->removeLine($cart, $lineId);
            } else {
                $cartService->updateLineQuantity($cart, $lineId, $quantity);
            }
            $this->loadCart();
        } catch (\InvalidArgumentException $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function removeLine(int $lineId): void
    {
        if (! $this->cartId) {
            return;
        }

        $cart = Cart::query()->withoutGlobalScopes()->find($this->cartId);

        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->loadCart();
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.cart-drawer');
    }
}
