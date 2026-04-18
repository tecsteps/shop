<?php

namespace App\Livewire\Storefront;

use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\PricingEngine;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public ?int $cartId = null;

    public array $linesData = [];

    public array $totals = [
        'subtotal' => 0,
        'discount' => 0,
        'total' => 0,
        'item_count' => 0,
    ];

    public string $currency = 'USD';

    public function mount(): void
    {
        $this->refreshCart();
    }

    #[On('cart:add-line')]
    public function addLine(int $variantId, int $quantity = 1): void
    {
        $service = app(CartService::class);
        $store = app('current_store');
        $cart = $service->getOrCreateForSession($store);

        try {
            $service->addLine($cart, $variantId, $quantity);
        } catch (InsufficientInventoryException $e) {
            $this->dispatch('cart:error', message: $e->getMessage());

            return;
        }

        $this->open = true;
        $this->refreshCart();
        $this->dispatch('cart:updated');
    }

    public function incrementLine(int $lineId): void
    {
        $this->adjustLine($lineId, +1);
    }

    public function decrementLine(int $lineId): void
    {
        $this->adjustLine($lineId, -1);
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->loadCart();
        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->refreshCart();
        $this->dispatch('cart:updated');
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function openDrawer(): void
    {
        $this->refreshCart();
        $this->open = true;
    }

    public function render()
    {
        return view('livewire.storefront.cart-drawer');
    }

    protected function adjustLine(int $lineId, int $delta): void
    {
        $cart = $this->loadCart();
        if (! $cart) {
            return;
        }

        $line = $cart->lines->firstWhere('id', $lineId);
        if (! $line) {
            return;
        }

        $new = $line->quantity + $delta;
        if ($new <= 0) {
            app(CartService::class)->removeLine($cart, $lineId);
        } else {
            try {
                app(CartService::class)->updateLineQuantity($cart, $lineId, $new);
            } catch (InsufficientInventoryException $e) {
                $this->dispatch('cart:error', message: $e->getMessage());
            }
        }

        $this->refreshCart();
        $this->dispatch('cart:updated');
    }

    protected function loadCart(): ?Cart
    {
        $cartId = session(CartService::SESSION_KEY);
        if (! $cartId) {
            return null;
        }

        return Cart::query()->with('lines.variant.product.media')->find($cartId);
    }

    protected function refreshCart(): void
    {
        $cart = $this->loadCart();

        if (! $cart) {
            $this->cartId = null;
            $this->linesData = [];
            $this->totals = ['subtotal' => 0, 'discount' => 0, 'total' => 0, 'item_count' => 0];

            return;
        }

        $this->cartId = $cart->id;
        $this->currency = $cart->currency;

        $pricing = app(PricingEngine::class)->calculateForCart($cart, app('current_store'));

        $this->linesData = $cart->lines->map(fn ($line): array => [
            'id' => $line->id,
            'title' => $line->variant?->product?->title ?? 'Item',
            'sku' => $line->variant?->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_total_amount' => $line->line_total_amount,
            'image_url' => $line->variant?->product?->media->first()?->storage_key,
        ])->all();

        $this->totals = [
            'subtotal' => $pricing->subtotal,
            'discount' => $pricing->discount,
            'total' => $pricing->subtotal - $pricing->discount,
            'item_count' => (int) $cart->lines->sum('quantity'),
        ];
    }
}
