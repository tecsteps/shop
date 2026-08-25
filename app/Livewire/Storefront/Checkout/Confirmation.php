<?php

namespace App\Livewire\Storefront\Checkout;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Confirmation extends Component
{
    use InteractsWithStore;

    public int $checkoutId;

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;

        $checkout = Checkout::findOrFail($checkoutId);

        $sessionCartId = session('cart_id');
        $customer = Auth::guard('customer')->user();

        $owns = (int) $checkout->cart_id === (int) $sessionCartId
            || ($customer !== null && (int) $customer->id === (int) $checkout->customer_id);

        if (! $owns) {
            abort(404);
        }
    }

    #[Computed]
    public function checkout(): Checkout
    {
        return Checkout::findOrFail($this->checkoutId);
    }

    #[Computed]
    public function order(): Order
    {
        return Order::where('checkout_id', $this->checkoutId)->firstOrFail();
    }

    #[Computed]
    public function orderLines(): SupportCollection
    {
        return $this->order->lines()
            ->with(['variant.product.media', 'variant.optionValues.option'])
            ->get();
    }

    #[Computed]
    public function currency(): string
    {
        return $this->order->currency ?? $this->store()->default_currency;
    }

    #[Computed]
    public function paymentMethodLabel(): string
    {
        return match ($this->order->payment_method) {
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            default => 'Credit Card',
        };
    }

    #[Computed]
    public function showViewOrder(): bool
    {
        $customer = Auth::guard('customer')->user();

        return $customer !== null && (int) $customer->id === (int) $this->order->customer_id;
    }
}
