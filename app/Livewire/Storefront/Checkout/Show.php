<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\UnserviceableAddressException;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\ShippingCalculator;
use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Show extends Component
{
    use ResolvesStore;

    public int $checkoutId;

    public string $email = '';

    /** @var array<string, mixed> */
    public array $shipping = [];

    /** @var array<string, mixed> */
    public array $billing = [];

    public bool $useShippingAsBilling = true;

    public ?int $shippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public string $cardHolder = '';

    public string $discountCodeInput = '';

    public ?string $payError = null;

    public ?int $savedAddressId = null;

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
        $checkout = $this->checkout();

        abort_if($checkout === null, 404);
        abort_if($checkout->status === CheckoutStatus::Expired, 410);

        if ($checkout->status === CheckoutStatus::Completed) {
            $this->redirect(route('storefront.checkout.confirmation', ['checkoutId' => $checkout->getKey()]), navigate: true);

            return;
        }

        $this->email = (string) ($checkout->email ?? Auth::guard('customer')->user()?->email ?? '');
        $this->shipping = $this->blankAddress((array) ($checkout->shipping_address_json ?? []));
        $this->billing = $this->blankAddress((array) ($checkout->billing_address_json ?? []));
        $this->shippingRateId = $checkout->shipping_method_id;
        $this->paymentMethod = $checkout->payment_method?->value ?? 'credit_card';

        if ($checkout->status === CheckoutStatus::Started && empty($checkout->shipping_address_json)) {
            $this->prefillFromDefaultAddress();
        }
    }

    public function saveContact(): void
    {
        $this->validate(['email' => 'required|email|max:255']);

        $checkout = $this->checkoutOrFail();
        $checkout->email = $this->email;
        $checkout->save();
    }

    public function saveAddress(): void
    {
        $this->validate(array_merge(
            ['email' => 'required|email|max:255'],
            $this->addressRules('shipping'),
            $this->useShippingAsBilling ? [] : $this->addressRules('billing'),
        ));

        $checkout = $this->checkoutOrFail();

        $shipping = $this->addressPayload($this->shipping);
        $billing = $this->useShippingAsBilling ? null : $this->addressPayload($this->billing);

        try {
            app(CheckoutService::class)->setAddress($checkout, $shipping, $billing, $this->useShippingAsBilling, $this->email);
        } catch (UnserviceableAddressException $exception) {
            $this->addError('shipping.country_code', $exception->getMessage());

            return;
        } catch (InvalidCheckoutTransitionException $exception) {
            $this->addError('shipping.address1', $exception->getMessage());

            return;
        }

        $this->shippingRateId = $this->checkoutOrFail()->shipping_method_id;
    }

    public function saveShipping(): void
    {
        $checkout = $this->checkoutOrFail();

        if (! app(ShippingCalculator::class)->requiresShipping($checkout->cart)) {
            app(CheckoutService::class)->setShippingMethod($checkout, null);

            return;
        }

        $this->validate(['shippingRateId' => 'required|integer']);

        try {
            app(CheckoutService::class)->setShippingMethod($checkout, $this->shippingRateId);
        } catch (ValidationException $exception) {
            $this->addError('shippingRateId', collect($exception->errors())->flatten()->first() ?? 'Invalid shipping method.');

            return;
        } catch (InvalidCheckoutTransitionException $exception) {
            $this->addError('shippingRateId', $exception->getMessage());
        }
    }

    public function applyDiscount(): void
    {
        $code = trim($this->discountCodeInput);

        if ($code === '') {
            $this->addError('discountCodeInput', 'Please enter a discount code.');

            return;
        }

        try {
            app(CheckoutService::class)->applyDiscount($this->checkoutOrFail(), $code, app(DiscountService::class));
        } catch (InvalidDiscountException $exception) {
            $this->addError('discountCodeInput', $exception->getMessage());

            return;
        } catch (InvalidCheckoutTransitionException $exception) {
            $this->addError('discountCodeInput', $exception->getMessage());

            return;
        }

        $this->discountCodeInput = '';
    }

    public function removeDiscount(): void
    {
        try {
            app(CheckoutService::class)->removeDiscount($this->checkoutOrFail());
        } catch (InvalidCheckoutTransitionException) {
        }
    }

    public function selectSavedAddress(): void
    {
        $customer = Auth::guard('customer')->user();

        if ($customer === null || $this->savedAddressId === null) {
            return;
        }

        $address = $customer->customerAddresses()->whereKey($this->savedAddressId)->first();

        if ($address !== null) {
            $this->shipping = $this->blankAddress((array) ($address->address_json ?? []));
        }
    }

    public function pay(): mixed
    {
        $this->payError = null;
        $this->validate($this->paymentRules());

        $method = PaymentMethod::from($this->paymentMethod);
        $details = [
            'payment_method' => $method->value,
            'card_number' => preg_replace('/\s+/', '', $this->cardNumber),
            'card_expiry' => $this->cardExpiry,
            'card_cvc' => $this->cardCvc,
            'card_holder' => $this->cardHolder,
        ];

        try {
            $order = app(CheckoutService::class)->pay($this->checkoutOrFail(), $method, $details);
        } catch (PaymentFailedException $exception) {
            $this->payError = 'Payment declined: '.$exception->getMessage();

            return null;
        } catch (InvalidCheckoutTransitionException $exception) {
            $this->payError = $exception->getMessage();

            return null;
        }

        session()->forget(['cart_id', 'storefront.discount_code']);

        return $this->redirect(
            route('storefront.checkout.confirmation', ['checkoutId' => $order->checkout_id]),
            navigate: true
        );
    }

    public function step(): int
    {
        $status = $this->checkoutOrFail()->status;

        return match ($status) {
            CheckoutStatus::Started => 1,
            CheckoutStatus::Addressed => 3,
            CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected => 4,
            default => 1,
        };
    }

    public function render()
    {
        $store = $this->store();
        $checkout = $this->checkout();

        abort_if($checkout === null, 404);
        abort_if($checkout->status === CheckoutStatus::Expired, 410);

        $catalog = app(StorefrontCatalog::class);
        $lines = $checkout->cart->cartLines()->with(['variant.product.productMedia', 'variant.optionValues'])->orderBy('id')->get();
        $totals = (array) ($checkout->totals_json ?? []);

        $shippingOptions = collect();

        if ($checkout->status !== CheckoutStatus::Started) {
            $shippingOptions = app(ShippingCalculator::class)->getAvailableRates(
                $store,
                (array) ($checkout->shipping_address_json ?? []),
                $checkout->cart
            );
        }

        $customer = Auth::guard('customer')->user();

        return view('livewire.storefront.checkout.show', [
            'store' => $store,
            'checkout' => $checkout,
            'step' => $this->step(),
            'summaryLines' => $lines->map(fn ($line): array => [
                'id' => $line->getKey(),
                'title' => $line->variant->product->title,
                'variant' => $catalog->variantOptionLabels($line->variant),
                'quantity' => $line->quantity,
                'total' => $line->line_total_amount,
                'image_url' => $catalog->primaryImageUrl($line->variant->product),
            ])->all(),
            'summaryTotals' => [
                'subtotal' => $totals['subtotal'] ?? 0,
                'discount' => $totals['discount'] ?? 0,
                'shipping' => $totals['shipping'] ?? 0,
                'tax' => $totals['tax_total'] ?? $totals['tax'] ?? 0,
                'total' => $totals['total'] ?? 0,
            ],
            'currency' => $checkout->cart->currency,
            'shippingOptions' => $shippingOptions,
            'requiresShipping' => app(ShippingCalculator::class)->requiresShipping($checkout->cart),
            'savedAddresses' => $customer?->customerAddresses()->orderByDesc('is_default')->orderBy('id')->get() ?? collect(),
            'isGuest' => $customer === null,
        ])->layout('storefront.layouts.app', [
            'title' => 'Checkout - '.$store->name,
            'metaDescription' => 'Complete your purchase at '.$store->name,
        ]);
    }

    protected function checkout(): ?Checkout
    {
        return Checkout::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->checkoutId)
            ->with(['cart.cartLines.variant.product', 'store'])
            ->first();
    }

    protected function checkoutOrFail(): Checkout
    {
        $checkout = $this->checkout();

        abort_if($checkout === null, 404);

        return $checkout;
    }

    /**
     * @return array<string, mixed>
     */
    protected function blankAddress(array $values = []): array
    {
        return array_merge([
            'first_name' => '',
            'last_name' => '',
            'company' => null,
            'address1' => '',
            'address2' => null,
            'city' => '',
            'province' => null,
            'province_code' => null,
            'country' => '',
            'country_code' => '',
            'postal_code' => '',
            'phone' => null,
        ], $values);
    }

    /**
     * @return array<string, string>
     */
    protected function addressRules(string $prefix): array
    {
        return [
            $prefix.'.first_name' => 'required|string|max:255',
            $prefix.'.last_name' => 'required|string|max:255',
            $prefix.'.address1' => 'required|string|max:500',
            $prefix.'.address2' => 'nullable|string|max:500',
            $prefix.'.city' => 'required|string|max:255',
            $prefix.'.province' => 'nullable|string|max:255',
            $prefix.'.country_code' => 'required|string|size:2',
            $prefix.'.postal_code' => 'required|string|max:20',
            $prefix.'.phone' => 'nullable|string|max:50',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function addressPayload(array $input): array
    {
        $names = [
            'DE' => 'Germany', 'AT' => 'Austria', 'FR' => 'France', 'NL' => 'Netherlands',
            'BE' => 'Belgium', 'IT' => 'Italy', 'ES' => 'Spain', 'US' => 'United States',
            'GB' => 'United Kingdom', 'CH' => 'Switzerland',
        ];

        $code = strtoupper((string) ($input['country_code'] ?? ''));

        return [
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'company' => $input['company'] ?? null,
            'address1' => $input['address1'],
            'address2' => $input['address2'] ?? null,
            'city' => $input['city'],
            'province' => $input['province'] ?? null,
            'province_code' => $input['province_code'] ?? null,
            'country' => $names[$code] ?? $code,
            'country_code' => $code,
            'postal_code' => $input['postal_code'],
            'phone' => $input['phone'] ?? null,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function paymentRules(): array
    {
        $rules = ['paymentMethod' => 'required|in:credit_card,paypal,bank_transfer'];

        if ($this->paymentMethod === PaymentMethod::CreditCard->value) {
            $rules['cardNumber'] = 'required|string|max:32';
            $rules['cardExpiry'] = 'required|string|max:7';
            $rules['cardCvc'] = 'required|string|max:4';
            $rules['cardHolder'] = 'required|string|max:255';
        }

        return $rules;
    }

    protected function prefillFromDefaultAddress(): void
    {
        $customer = Auth::guard('customer')->user();
        $default = $customer?->customerAddresses()->where('is_default', true)->first()
            ?? $customer?->customerAddresses()->orderBy('id')->first();

        if ($default !== null) {
            $this->shipping = $this->blankAddress((array) ($default->address_json ?? []));
        }
    }
}
