<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCartOperationException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Services\CartService;
use App\Services\DiscountService;
use App\Services\ShippingCalculator;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public int $storeId;

    public string $discountCode = '';

    public ?string $appliedDiscountCode = null;

    public string $shippingCountry = 'DE';

    public string $shippingPostalCode = '';

    public string $shippingProvinceCode = '';

    public ?string $cartMessage = null;

    public function mount(): void
    {
        $this->storeId = $this->store()->getKey();
        $this->appliedDiscountCode = trim((string) session('cart_discount_code')) ?: null;
        $this->discountCode = $this->appliedDiscountCode ?? '';
    }

    #[On('cart-updated')]
    public function refreshCart(): void {}

    public function increaseQuantity(int $lineId): void
    {
        $line = $this->cartLine($lineId);

        if (! $line instanceof CartLine) {
            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($line->cart, $line->getKey(), $line->quantity + 1);
            $this->cartMessage = null;
            $this->dispatch('cart-updated');
        } catch (InsufficientInventoryException|InvalidCartOperationException $exception) {
            $this->cartMessage = $exception->getMessage();
        }
    }

    public function decreaseQuantity(int $lineId): void
    {
        $line = $this->cartLine($lineId);

        if (! $line instanceof CartLine) {
            return;
        }

        app(CartService::class)->updateLineQuantity($line->cart, $line->getKey(), $line->quantity - 1);
        $this->cartMessage = null;
        $this->dispatch('cart-updated');
    }

    public function removeLine(int $lineId): void
    {
        $line = $this->cartLine($lineId);

        if (! $line instanceof CartLine) {
            return;
        }

        app(CartService::class)->removeLine($line->cart, $line->getKey());
        $this->cartMessage = null;
        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        $this->validate([
            'discountCode' => ['required', 'string', 'max:50'],
        ]);

        $cart = $this->cart();

        if (! $cart instanceof Cart) {
            return;
        }

        $code = trim($this->discountCode);

        try {
            $discount = app(DiscountService::class)->validate($code, $this->store(), $cart);
            app(DiscountService::class)->calculate($discount, $this->subtotal(), $this->lines()->all());
        } catch (InvalidDiscountException $exception) {
            $this->removeDiscount();

            throw ValidationException::withMessages([
                'discountCode' => $exception->getMessage(),
            ]);
        }

        $this->appliedDiscountCode = $code;
        $this->discountCode = $code;
        session(['cart_discount_code' => $code]);
        $this->resetErrorBag('discountCode');
    }

    public function removeDiscount(): void
    {
        $this->appliedDiscountCode = null;
        $this->discountCode = '';
        session()->forget('cart_discount_code');
        $this->resetErrorBag('discountCode');
    }

    public function estimateShipping(): void
    {
        $this->validate([
            'shippingCountry' => ['required', 'string', 'size:2'],
            'shippingPostalCode' => ['nullable', 'string', 'max:20'],
            'shippingProvinceCode' => ['nullable', 'string', 'max:20'],
        ]);

        $this->resetErrorBag('shippingCountry');
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

    /**
     * @return Collection<int, ShippingRate>
     */
    public function availableRates(): Collection
    {
        if (! $this->requiresShipping() || $this->shippingCountry === '') {
            return collect();
        }

        return app(ShippingCalculator::class)->getAvailableRates($this->store(), $this->shippingAddress());
    }

    public function requiresShipping(): bool
    {
        $cart = $this->cart();

        return $cart instanceof Cart && app(ShippingCalculator::class)->requiresShipping($cart);
    }

    /**
     * @return array<int, int>
     */
    public function shippingRateAmounts(): array
    {
        $cart = $this->cart();

        if (! $cart instanceof Cart) {
            return [];
        }

        return $this->availableRates()
            ->mapWithKeys(fn (ShippingRate $rate): array => [
                $rate->getKey() => app(ShippingCalculator::class)->calculate($rate, $cart) ?? 0,
            ])
            ->all();
    }

    public function estimatedShippingAmount(): ?int
    {
        if (! $this->requiresShipping()) {
            return 0;
        }

        $amounts = $this->shippingRateAmounts();

        return $amounts === [] ? null : min($amounts);
    }

    public function discountResult(): ?DiscountResult
    {
        $cart = $this->cart();
        $code = trim((string) $this->appliedDiscountCode);

        if (! $cart instanceof Cart || $code === '') {
            return null;
        }

        try {
            $discount = app(DiscountService::class)->validate($code, $this->store(), $cart);

            return app(DiscountService::class)->calculate($discount, $this->subtotal(), $this->lines()->all());
        } catch (InvalidDiscountException) {
            return null;
        }
    }

    public function discountAmount(): int
    {
        return $this->discountResult()?->amount ?? 0;
    }

    public function discountFreeShipping(): bool
    {
        return $this->discountResult()?->freeShipping ?? false;
    }

    public function estimatedTotal(): int
    {
        $shipping = $this->discountFreeShipping() ? 0 : ($this->estimatedShippingAmount() ?? 0);

        return max(0, $this->subtotal() - $this->discountAmount() + $shipping);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart.show', [
            'cart' => $this->cart(),
            'lines' => $this->lines(),
            'lineCount' => $this->lineCount(),
            'subtotal' => $this->subtotal(),
            'discountAmount' => $this->discountAmount(),
            'discountFreeShipping' => $this->discountFreeShipping(),
            'rates' => $this->availableRates(),
            'rateAmounts' => $this->shippingRateAmounts(),
            'estimatedShipping' => $this->estimatedShippingAmount(),
            'estimatedTotal' => $this->estimatedTotal(),
            'requiresShipping' => $this->requiresShipping(),
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

    /**
     * @return array<string, string>
     */
    private function shippingAddress(): array
    {
        return [
            'country' => strtoupper($this->shippingCountry),
            'country_code' => strtoupper($this->shippingCountry),
            'postal_code' => $this->shippingPostalCode,
            'province_code' => strtoupper($this->shippingProvinceCode),
        ];
    }
}
