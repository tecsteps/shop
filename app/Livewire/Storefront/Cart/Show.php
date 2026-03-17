<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public ?int $cartId = null;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public int $subtotal = 0;

    public int $itemCount = 0;

    public string $discountCode = '';

    public function mount(): void
    {
        $this->loadCart();
    }

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
                'line_subtotal_amount' => $line->line_subtotal_amount,
                'line_total_amount' => $line->line_total_amount,
            ];
        })->toArray();

        $this->subtotal = $cart->lines->sum('line_total_amount');
        $this->itemCount = $cart->lines->sum('quantity');
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
            $this->dispatch('cart-updated');
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
        $this->dispatch('cart-updated');
    }

    public function proceedToCheckout(): void
    {
        if (! $this->cartId || count($this->lines) === 0) {
            return;
        }

        $cart = Cart::query()->withoutGlobalScopes()->find($this->cartId);

        if (! $cart) {
            return;
        }

        $checkoutService = app(CheckoutService::class);
        $checkout = $checkoutService->createFromCart($cart);

        $this->redirect(route('storefront.checkout.show', $checkout->id));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.cart.show');
    }
}
