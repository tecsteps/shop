<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\DispatchesStorefrontAnalytics;
use App\Models\Cart;
use App\Models\CartLine;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    use DispatchesStorefrontAnalytics;

    public bool $isOpen = false;

    /** @var array<int, int> */
    public array $quantities = [];

    public string $discountCode = '';

    #[On('open-cart-drawer')]
    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->syncQuantities(app(CartService::class));
    }

    public function updatedQuantities(int $quantity, string $lineId, CartService $carts): void
    {
        $this->updateLine((int) $lineId, $quantity, $carts);
    }

    public function increaseQuantity(int $lineId, CartService $carts): void
    {
        $line = $this->cart($carts)->lines()->findOrFail($lineId);
        $this->updateLine($lineId, min(9999, (int) $line->quantity + 1), $carts);
    }

    public function decreaseQuantity(int $lineId, CartService $carts): void
    {
        $line = $this->cart($carts)->lines()->findOrFail($lineId);
        $this->updateLine($lineId, max(1, (int) $line->quantity - 1), $carts);
    }

    public function removeItem(int $lineId, CartService $carts): void
    {
        $cart = $this->cart($carts);
        $line = $cart->lines()->with('variant', 'cart')->findOrFail($lineId);
        $carts->remove($cart, $lineId);
        $this->dispatchStorefrontAnalytics('remove_from_cart', $this->cartLineProperties($line, (int) $line->quantity));
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function applyDiscount(CartService $carts): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);
        $carts->applyDiscount($this->cart($carts), $this->discountCode);
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function removeDiscount(CartService $carts): void
    {
        $carts->removeDiscount($this->cart($carts));
        $this->discountCode = '';
        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    public function render(CartService $carts): mixed
    {
        $cart = $carts->findActiveCart(app('current_store'), auth('customer')->user());
        $this->fillQuantities($cart);

        return view('storefront.components.cart-drawer', ['cart' => $cart]);
    }

    private function cart(CartService $carts): Cart
    {
        return $carts->getOrCreateActiveCart(app('current_store'), auth('customer')->user());
    }

    private function syncQuantities(CartService $carts): void
    {
        $this->fillQuantities($this->currentCart($carts));
    }

    private function currentCart(CartService $carts): ?Cart
    {
        return $carts->findActiveCart(app('current_store'), auth('customer')->user());
    }

    private function updateLine(int $lineId, int $quantity, CartService $carts): void
    {
        $cart = $this->cart($carts);
        $line = $cart->lines()->with('variant', 'cart')->findOrFail($lineId);
        $previousQuantity = (int) $line->quantity;
        $carts->updateQuantity($cart, $lineId, $quantity);

        if ($quantity !== $previousQuantity) {
            $eventType = $quantity > $previousQuantity ? 'add_to_cart' : 'remove_from_cart';
            $this->dispatchStorefrontAnalytics($eventType, $this->cartLineProperties($line, abs($quantity - $previousQuantity)));
        }

        $this->dispatch('cart-updated');
        $this->syncQuantities($carts);
    }

    /** @return array<string, mixed> */
    private function cartLineProperties(CartLine $line, int $quantity): array
    {
        return [
            'product_id' => $line->variant?->product_id,
            'variant_id' => $line->variant_id,
            'quantity' => $quantity,
            'price_amount' => (int) ($line->variant?->price_amount ?? $line->unit_price_amount),
            'currency' => $line->cart?->currency,
        ];
    }

    private function fillQuantities(?Cart $cart): void
    {
        $this->quantities = $cart?->lines->mapWithKeys(static fn ($line): array => [$line->id => $line->quantity])->all() ?? [];
        $this->discountCode = $cart?->discount_code ?? '';
    }
}
