<?php

namespace App\Services\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;
use Illuminate\Contracts\Session\Session;

class CartSession
{
    private const SESSION_KEY = 'cart_id';

    public function __construct(private readonly Session $session, private readonly CartService $cartService) {}

    public function current(): ?Cart
    {
        $id = $this->session->get(self::SESSION_KEY);
        if (! $id) {
            return null;
        }

        $cart = Cart::with('lines.variant.product.media', 'lines.variant.inventory')->find($id);

        if (! $cart || $cart->status !== CartStatus::Active) {
            $this->session->forget(self::SESSION_KEY);

            return null;
        }

        return $cart;
    }

    public function ensureCart(): Cart
    {
        $existing = $this->current();
        if ($existing) {
            return $existing;
        }

        $store = app('current_store');
        $customer = auth('customer')->user();
        $cart = $this->cartService->createForStore($store, $customer);
        $this->session->put(self::SESSION_KEY, $cart->id);

        return $cart->fresh(['lines']);
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function lineCount(): int
    {
        $cart = $this->current();

        return $cart?->lineCount() ?? 0;
    }
}
