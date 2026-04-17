<?php

namespace App\Livewire\Storefront\Cart;

use App\Models\Cart;
use App\Models\CartLine;
use App\Services\Cart\CartService;
use App\Services\Cart\CartSession;
use App\Services\Discounts\DiscountService;
use App\Services\Pricing\PricingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public ?Cart $cart = null;

    public string $discountCode = '';

    public ?string $discountError = null;

    public ?string $discountSuccess = null;

    public array $totals = [];

    public function mount(CartSession $cartSession, PricingService $pricingService, DiscountService $discountService): void
    {
        $this->cart = $cartSession->current();
        $this->recomputeTotals($pricingService, $discountService);
    }

    public function updateQuantity(int $lineId, int $quantity, CartService $cartService, PricingService $pricingService, DiscountService $discountService): void
    {
        $line = CartLine::find($lineId);
        if ($line) {
            $cartService->updateLineQuantity($line, $quantity);
            $this->cart?->load('lines.variant.product.media', 'lines.variant.inventory');
            $this->dispatch('cart-updated');
            $this->recomputeTotals($pricingService, $discountService);
        }
    }

    public function removeLine(int $lineId, CartService $cartService, PricingService $pricingService, DiscountService $discountService): void
    {
        $line = CartLine::find($lineId);
        if ($line) {
            $cartService->removeLine($line);
            $this->cart?->load('lines.variant.product.media', 'lines.variant.inventory');
            $this->dispatch('cart-updated');
            $this->recomputeTotals($pricingService, $discountService);
        }
    }

    public function applyDiscount(DiscountService $discountService, PricingService $pricingService): void
    {
        $this->discountError = null;
        $this->discountSuccess = null;

        $code = trim($this->discountCode);
        if ($code === '') {
            return;
        }

        $discount = $discountService->findCode(app('current_store'), $code);
        if (! $discount) {
            $this->discountError = 'Invalid or expired code.';
            session()->forget('cart_discount_code');

            return;
        }

        session()->put('cart_discount_code', $code);
        $this->discountSuccess = 'Discount "'.$code.'" applied.';
        $this->recomputeTotals($pricingService, $discountService);
    }

    public function removeDiscount(PricingService $pricingService, DiscountService $discountService): void
    {
        session()->forget('cart_discount_code');
        $this->discountCode = '';
        $this->discountSuccess = null;
        $this->recomputeTotals($pricingService, $discountService);
    }

    private function recomputeTotals(PricingService $pricingService, DiscountService $discountService): void
    {
        if (! $this->cart) {
            $this->totals = [];

            return;
        }

        $code = session()->get('cart_discount_code');
        $discount = $code ? $discountService->findCode(app('current_store'), $code) : null;
        if ($code) {
            $this->discountCode = $code;
        }

        $totals = $pricingService->computeTotals($this->cart, null, $discount);
        $this->totals = $totals->toArray();
    }

    public function render()
    {
        return view('livewire.storefront.cart.show')->title('Cart');
    }
}
