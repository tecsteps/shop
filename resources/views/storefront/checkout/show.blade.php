@extends('storefront.layout')

@section('content')
@php
    $shipping = $shippingAddress ?? [];
    $billing = $billingAddress ?? [];
    $billingSame = old('billing_same_as_shipping', '1');
    $selectedRateId = old('shipping_rate_id', $checkout->shipping_method_id);
    $selectedPaymentMethod = old('payment_method', $paymentMethod);
@endphp

<section class="space-y-6">
    <header class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Checkout</h1>
        <p class="text-sm text-zinc-600">Complete your contact, shipping, and payment details to place your order.</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <form method="POST" action="{{ route('storefront.checkout.submit', ['checkoutId' => $checkout->id]) }}" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-5 sm:p-6" id="checkout-form">
            @csrf

            <section class="space-y-3">
                <h2 class="text-lg font-semibold text-zinc-900">1. Contact information</h2>
                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-700">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $checkout->email ?? $currentCustomer?->email) }}" required autocomplete="email" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
                @if (! $currentCustomer)
                    <p class="text-xs text-zinc-500">Already have an account? <a href="{{ route('storefront.account.login') }}" class="font-medium text-zinc-900 hover:underline">Log in</a></p>
                @endif
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-zinc-900">2. Shipping address</h2>
                    @if ($savedAddresses->isNotEmpty())
                        <span class="text-xs text-zinc-500">{{ $savedAddresses->count() }} saved address(es)</span>
                    @endif
                </div>

                @if ($savedAddresses->isNotEmpty())
                    <details class="rounded-md border border-zinc-200 bg-zinc-50 p-3 text-sm">
                        <summary class="cursor-pointer font-medium text-zinc-800">View saved addresses</summary>
                        <ul class="mt-3 space-y-2 text-zinc-600">
                            @foreach ($savedAddresses as $saved)
                                <li class="rounded-md border border-zinc-200 bg-white p-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $saved->label ?: 'Address' }}</p>
                                    <p>{{ ($saved->address_json['first_name'] ?? '').' '.($saved->address_json['last_name'] ?? '') }}</p>
                                    <p>{{ $saved->address_json['address1'] ?? '' }}, {{ $saved->address_json['city'] ?? '' }}</p>
                                    <p>{{ $saved->address_json['zip'] ?? '' }} {{ $saved->address_json['country'] ?? '' }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="shipping_first_name" class="block text-sm font-medium text-zinc-700">First name</label>
                        <input id="shipping_first_name" type="text" name="shipping[first_name]" value="{{ old('shipping.first_name', $shipping['first_name'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" @required($requiresShipping)>
                    </div>
                    <div>
                        <label for="shipping_last_name" class="block text-sm font-medium text-zinc-700">Last name</label>
                        <input id="shipping_last_name" type="text" name="shipping[last_name]" value="{{ old('shipping.last_name', $shipping['last_name'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" @required($requiresShipping)>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="shipping_address1" class="block text-sm font-medium text-zinc-700">Address line 1</label>
                        <input id="shipping_address1" type="text" name="shipping[address1]" value="{{ old('shipping.address1', $shipping['address1'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" @required($requiresShipping)>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="shipping_address2" class="block text-sm font-medium text-zinc-700">Address line 2</label>
                        <input id="shipping_address2" type="text" name="shipping[address2]" value="{{ old('shipping.address2', $shipping['address2'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label for="shipping_city" class="block text-sm font-medium text-zinc-700">City</label>
                        <input id="shipping_city" type="text" name="shipping[city]" value="{{ old('shipping.city', $shipping['city'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" @required($requiresShipping)>
                    </div>
                    <div>
                        <label for="shipping_zip" class="block text-sm font-medium text-zinc-700">Postal code</label>
                        <input id="shipping_zip" type="text" name="shipping[zip]" value="{{ old('shipping.zip', $shipping['zip'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" @required($requiresShipping)>
                    </div>
                    <div>
                        <label for="shipping_country" class="block text-sm font-medium text-zinc-700">Country</label>
                        <input id="shipping_country" type="text" name="shipping[country]" value="{{ old('shipping.country', $shipping['country'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" @required($requiresShipping)>
                    </div>
                    <div>
                        <label for="shipping_country_code" class="block text-sm font-medium text-zinc-700">Country code</label>
                        <input id="shipping_country_code" type="text" name="shipping[country_code]" value="{{ old('shipping.country_code', $shipping['country_code'] ?? '') }}" maxlength="2" class="mt-1 w-full rounded-md border-zinc-300 text-sm uppercase focus:border-zinc-500 focus:ring-zinc-500" @required($requiresShipping)>
                    </div>
                    <div>
                        <label for="shipping_province" class="block text-sm font-medium text-zinc-700">State / Province</label>
                        <input id="shipping_province" type="text" name="shipping[province]" value="{{ old('shipping.province', $shipping['province'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label for="shipping_province_code" class="block text-sm font-medium text-zinc-700">State code</label>
                        <input id="shipping_province_code" type="text" name="shipping[province_code]" value="{{ old('shipping.province_code', $shipping['province_code'] ?? '') }}" maxlength="10" class="mt-1 w-full rounded-md border-zinc-300 text-sm uppercase focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="shipping_phone" class="block text-sm font-medium text-zinc-700">Phone</label>
                        <input id="shipping_phone" type="tel" name="shipping[phone]" value="{{ old('shipping.phone', $shipping['phone'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                </div>
            </section>

            @if ($requiresShipping)
                <section class="space-y-3">
                    <h2 class="text-lg font-semibold text-zinc-900">3. Shipping method</h2>

                    @if ($availableRates->isEmpty())
                        <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">No shipping methods are available for your address. Please verify your address or contact support.</p>
                    @else
                        <fieldset class="space-y-2">
                            <legend class="sr-only">Shipping methods</legend>
                            @foreach ($availableRates as $rate)
                                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-zinc-300 px-4 py-3 text-sm hover:border-zinc-500">
                                    <span class="flex items-center gap-3">
                                        <input type="radio" name="shipping_rate_id" value="{{ $rate->id }}" class="border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked((string) $selectedRateId === (string) $rate->id)>
                                        <span>
                                            <span class="block font-semibold text-zinc-900">{{ $rate->name }}</span>
                                            <span class="block text-xs text-zinc-500">{{ $rate->zone->name }}</span>
                                        </span>
                                    </span>
                                    <span class="font-semibold text-zinc-900">{{ number_format(((int) ($rate->config_json['amount'] ?? 0)) / 100, 2, '.', ',') }} {{ $currency }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endif
                </section>
            @endif

            <section class="space-y-3">
                <h2 class="text-lg font-semibold text-zinc-900">4. Billing and payment</h2>

                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" name="billing_same_as_shipping" value="1" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked((string) $billingSame === '1' || (string) $billingSame === 'true') data-billing-same>
                    Billing address same as shipping
                </label>

                <div class="grid gap-3 rounded-lg border border-zinc-200 p-4" data-billing-fields @class(['hidden' => (string) $billingSame === '1' || (string) $billingSame === 'true'])>
                    <h3 class="text-sm font-semibold text-zinc-900">Billing address</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="billing_first_name" class="block text-sm font-medium text-zinc-700">First name</label>
                            <input id="billing_first_name" type="text" name="billing[first_name]" value="{{ old('billing.first_name', $billing['first_name'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        </div>
                        <div>
                            <label for="billing_last_name" class="block text-sm font-medium text-zinc-700">Last name</label>
                            <input id="billing_last_name" type="text" name="billing[last_name]" value="{{ old('billing.last_name', $billing['last_name'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="billing_address1" class="block text-sm font-medium text-zinc-700">Address line 1</label>
                            <input id="billing_address1" type="text" name="billing[address1]" value="{{ old('billing.address1', $billing['address1'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="billing_city" class="block text-sm font-medium text-zinc-700">City</label>
                            <input id="billing_city" type="text" name="billing[city]" value="{{ old('billing.city', $billing['city'] ?? '') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-zinc-700">Payment method</p>
                    <label class="flex items-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm hover:border-zinc-500">
                        <input type="radio" name="payment_method" value="credit_card" class="border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked($selectedPaymentMethod === 'credit_card')>
                        Credit Card
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm hover:border-zinc-500">
                        <input type="radio" name="payment_method" value="paypal" class="border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked($selectedPaymentMethod === 'paypal')>
                        PayPal
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm hover:border-zinc-500">
                        <input type="radio" name="payment_method" value="bank_transfer" class="border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked($selectedPaymentMethod === 'bank_transfer')>
                        Bank Transfer
                    </label>
                </div>

                <div class="grid gap-3 rounded-lg border border-zinc-200 p-4" data-payment-card>
                    <h3 class="text-sm font-semibold text-zinc-900">Card details</h3>
                    <div>
                        <label for="card_number" class="block text-sm font-medium text-zinc-700">Card number</label>
                        <input id="card_number" type="text" name="card[number]" value="{{ old('card.number') }}" inputmode="numeric" autocomplete="cc-number" placeholder="4242 4242 4242 4242" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div>
                        <label for="card_name" class="block text-sm font-medium text-zinc-700">Cardholder name</label>
                        <input id="card_name" type="text" name="card[name]" value="{{ old('card.name') }}" autocomplete="cc-name" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="card_expiry" class="block text-sm font-medium text-zinc-700">Expiry (MM/YY)</label>
                            <input id="card_expiry" type="text" name="card[expiry]" value="{{ old('card.expiry') }}" autocomplete="cc-exp" placeholder="12/28" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        </div>
                        <div>
                            <label for="card_cvc" class="block text-sm font-medium text-zinc-700">CVC</label>
                            <input id="card_cvc" type="text" name="card[cvc]" value="{{ old('card.cvc') }}" autocomplete="cc-csc" placeholder="123" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                        </div>
                    </div>
                </div>

                <div class="hidden rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700" data-payment-paypal>
                    Your PayPal payment will be processed securely after you submit this form.
                </div>

                <div class="hidden rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" data-payment-bank>
                    After placing your order, bank transfer instructions will appear on the confirmation page. Your order will remain pending until payment arrives.
                </div>
            </section>

            <section class="space-y-2">
                <h2 class="text-lg font-semibold text-zinc-900">Optional discount code</h2>
                <div>
                    <label for="discount_code" class="block text-sm font-medium text-zinc-700">Discount code</label>
                    <input id="discount_code" type="text" name="discount_code" value="{{ old('discount_code', $checkout->discount_code) }}" placeholder="Enter discount code" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                </div>
            </section>

            <button type="submit" class="w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700" data-submit-label>
                {{ $selectedPaymentMethod === 'bank_transfer' ? 'Place order' : 'Pay now' }} - {{ number_format(((int) ($totals['total'] ?? 0)) / 100, 2, '.', ',') }} {{ $totals['currency'] ?? $currency }}
            </button>
        </form>

        <aside class="h-fit space-y-4 rounded-xl border border-zinc-200 bg-white p-5 lg:sticky lg:top-4">
            <h2 class="text-lg font-semibold text-zinc-900">Order Summary</h2>

            <div class="divide-y divide-zinc-200">
                @foreach ($checkout->cart->lines as $line)
                    @php
                        $variant = $line->variant;
                        $title = $variant->product->title;
                        $variantLabel = $variant->optionValues->map(fn ($value) => $value->option->name.': '.$value->value)->implode(' / ');
                    @endphp

                    <div class="py-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-900">{{ $title }}</p>
                                @if ($variantLabel !== '')
                                    <p class="text-xs text-zinc-500">{{ $variantLabel }}</p>
                                @endif
                                <p class="text-xs text-zinc-500">Quantity: {{ $line->quantity }}</p>
                            </div>
                            <p class="font-semibold text-zinc-900">{{ number_format($line->line_total_amount / 100, 2, '.', ',') }} {{ $variant->currency }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <dl class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-600">Subtotal</dt>
                    <dd class="font-medium text-zinc-900">{{ number_format(((int) ($totals['subtotal'] ?? 0)) / 100, 2, '.', ',') }} {{ $totals['currency'] ?? $currency }}</dd>
                </div>
                @if (((int) ($totals['discount'] ?? 0)) > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-600">Discount</dt>
                        <dd class="font-medium text-emerald-700">-{{ number_format(((int) ($totals['discount'] ?? 0)) / 100, 2, '.', ',') }} {{ $totals['currency'] ?? $currency }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-600">Shipping</dt>
                    <dd class="font-medium text-zinc-900">{{ number_format(((int) ($totals['shipping'] ?? 0)) / 100, 2, '.', ',') }} {{ $totals['currency'] ?? $currency }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-600">Tax</dt>
                    <dd class="font-medium text-zinc-900">{{ number_format(((int) ($totals['tax'] ?? 0)) / 100, 2, '.', ',') }} {{ $totals['currency'] ?? $currency }}</dd>
                </div>
                <div class="flex items-center justify-between border-t border-zinc-200 pt-2">
                    <dt class="text-base font-semibold text-zinc-900">Total</dt>
                    <dd class="text-base font-semibold text-zinc-900">{{ number_format(((int) ($totals['total'] ?? 0)) / 100, 2, '.', ',') }} {{ $totals['currency'] ?? $currency }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</section>

<script>
    (() => {
        const form = document.getElementById('checkout-form');
        if (!form) return;

        const billingToggle = form.querySelector('[data-billing-same]');
        const billingFields = form.querySelector('[data-billing-fields]');
        const submitLabel = form.querySelector('[data-submit-label]');
        const paymentCard = form.querySelector('[data-payment-card]');
        const paymentPaypal = form.querySelector('[data-payment-paypal]');
        const paymentBank = form.querySelector('[data-payment-bank]');

        const total = '{{ number_format(((int) ($totals['total'] ?? 0)) / 100, 2, '.', ',') }} {{ $totals['currency'] ?? $currency }}';

        const toggleBilling = () => {
            if (!billingToggle || !billingFields) return;
            billingFields.classList.toggle('hidden', billingToggle.checked);
        };

        const updatePaymentSections = () => {
            const selected = form.querySelector('input[name="payment_method"]:checked')?.value || 'credit_card';
            paymentCard?.classList.toggle('hidden', selected !== 'credit_card');
            paymentPaypal?.classList.toggle('hidden', selected !== 'paypal');
            paymentBank?.classList.toggle('hidden', selected !== 'bank_transfer');

            if (submitLabel) {
                submitLabel.textContent = (selected === 'bank_transfer' ? 'Place order' : (selected === 'paypal' ? 'Pay with PayPal' : 'Pay now')) + ' - ' + total;
            }
        };

        billingToggle?.addEventListener('change', toggleBilling);
        form.querySelectorAll('input[name="payment_method"]').forEach((input) => {
            input.addEventListener('change', updatePaymentSections);
        });

        toggleBilling();
        updatePaymentSections();
    })();
</script>
@endsection
