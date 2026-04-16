<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Services\Cart\CartSession;
use App\Services\Checkout\CheckoutService;
use App\Services\Discounts\DiscountService;
use App\Services\Orders\OrderService;
use App\Services\Orders\PaymentFailedException;
use App\Services\Pricing\PricingService;
use App\Services\Shipping\ShippingService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    public ?Cart $cart = null;

    public ?Checkout $checkout = null;

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|max:120')]
    public string $firstName = '';

    #[Validate('required|string|max:120')]
    public string $lastName = '';

    #[Validate('required|string|max:255')]
    public string $address1 = '';

    #[Validate('required|string|max:120')]
    public string $city = '';

    #[Validate('required|string|max:20')]
    public string $zip = '';

    #[Validate('required|string|size:2')]
    public string $country = 'DE';

    public ?int $shippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '4242424242424242';

    public string $cardName = '';

    public string $cardExpiry = '12/29';

    public string $cardCvc = '123';

    public array $totals = [];

    public ?string $errorMessage = null;

    public function mount(CartSession $cartSession, CheckoutService $checkoutService, PricingService $pricingService, DiscountService $discountService): void
    {
        $this->cart = $cartSession->current();

        if (! $this->cart || $this->cart->lines->isEmpty()) {
            $this->redirect(route('storefront.cart.show'));

            return;
        }

        $this->checkout = $this->cart->checkouts()->latest()->first()
            ?? $checkoutService->startFromCart($this->cart);

        if ($customer = auth('customer')->user()) {
            $this->email = (string) $customer->email;
            $default = $customer->defaultAddress();
            if ($default && is_array($default->address_json)) {
                $a = $default->address_json;
                $this->firstName = (string) ($a['first_name'] ?? '');
                $this->lastName = (string) ($a['last_name'] ?? '');
                $this->address1 = (string) ($a['address1'] ?? '');
                $this->city = (string) ($a['city'] ?? '');
                $this->zip = (string) ($a['zip'] ?? '');
                $this->country = (string) ($a['country'] ?? 'DE');
            }
        }

        $this->recomputeTotals($pricingService, $discountService);
    }

    public function shippingRates(ShippingService $shippingService): \Illuminate\Support\Collection
    {
        if (! $this->country) {
            return collect();
        }

        return $shippingService->ratesForCountry(app('current_store'), $this->country);
    }

    public function updatedCountry(): void
    {
        $this->shippingRateId = null;
    }

    public function updatedShippingRateId(PricingService $pricingService, DiscountService $discountService): void
    {
        $this->recomputeTotals($pricingService, $discountService);
    }

    private function recomputeTotals(PricingService $pricingService, DiscountService $discountService): void
    {
        if (! $this->cart) {
            return;
        }

        $rate = $this->shippingRateId ? ShippingRate::find($this->shippingRateId) : null;
        $code = session()->get('cart_discount_code');
        $discount = $code ? $discountService->findCode(app('current_store'), $code) : null;

        $totals = $pricingService->computeTotals($this->cart, $rate, $discount);
        $this->totals = $totals->toArray();
    }

    public function placeOrder(
        CheckoutService $checkoutService,
        OrderService $orderService,
        CartSession $cartSession,
    ): mixed {
        $this->validate();
        $this->errorMessage = null;

        if (! $this->cart) {
            return null;
        }

        $shipping = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address1' => $this->address1,
            'city' => $this->city,
            'zip' => $this->zip,
            'country' => strtoupper($this->country),
        ];

        $checkoutService->setContact($this->checkout, $this->email);
        $checkoutService->setAddresses($this->checkout, $shipping);
        if ($this->shippingRateId) {
            $checkoutService->selectShipping($this->checkout, (int) $this->shippingRateId);
        }
        if ($code = session()->get('cart_discount_code')) {
            $checkoutService->applyDiscountCode($this->checkout, $code);
        }
        $checkoutService->selectPayment($this->checkout, $this->paymentMethod);

        $this->checkout->refresh();

        try {
            $order = $orderService->placeFromCheckout($this->checkout, [
                'method' => $this->paymentMethod,
                'card_number' => $this->cardNumber,
                'card_name' => $this->cardName,
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
            ]);
        } catch (PaymentFailedException $e) {
            $this->errorMessage = 'Payment failed: '.$e->getMessage();

            return null;
        }

        $cartSession->forget();
        session()->forget('cart_discount_code');

        return $this->redirect(route('storefront.checkout.confirmation', ['orderNumber' => ltrim($order->order_number, '#')]), navigate: false);
    }

    public function render()
    {
        $rates = $this->shippingRates(app(ShippingService::class));

        return view('livewire.storefront.checkout.index', compact('rates'))
            ->title('Checkout');
    }
}
