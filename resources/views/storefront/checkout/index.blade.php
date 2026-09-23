
@php
    $checkoutCurrency = data_get($currentStore ?? null, 'currency', 'EUR');
    $checkoutTotal = (int) data_get($checkout, 'total_amount', data_get($checkout, 'subtotal_amount', 0));
    $selectedPaymentMethod = data_get($checkout, 'payment_method', 'credit_card');
@endphp

@section('title', 'Checkout')
<div>
<section class="mx-auto max-w-7xl px-4 py-7 sm:px-6 sm:py-10 lg:px-8">
        <a href="/cart" class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-stone-600 hover:text-stone-950 focus-visible:outline-2 focus-visible:outline-blue-700 dark:text-stone-300 dark:hover:text-white">@include('storefront.components.icon', ['name' => 'chevron-left', 'class' => 'size-4']) Back to cart</a>
        <div class="mt-5 grid gap-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(19rem,.8fr)] lg:gap-12">
            <div class="min-w-0">
                <h1 class="text-3xl font-semibold tracking-tight">Checkout</h1>
                <ol class="mt-5 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium text-stone-500 dark:text-stone-400" aria-label="Checkout progress">
                    @foreach (['Contact', 'Address', 'Delivery', 'Payment'] as $index => $stepName)<li class="inline-flex items-center gap-2"><span @if ($checkoutStep === $index + 1) aria-current="step" @endif class="{{ $checkoutStep >= $index + 1 ? 'text-blue-800 dark:text-blue-300' : '' }}">{{ $index + 1 }}. {{ $stepName }}</span>@if ($index < 3)<span aria-hidden="true">/</span>@endif</li>@endforeach
                </ol>

                <div class="mt-7 grid gap-4">
                    <section aria-labelledby="checkout-contact-heading" class="overflow-hidden rounded-2xl border border-stone-200 dark:border-stone-800">
                        <div class="flex items-center justify-between gap-4 bg-stone-50 px-5 py-4 dark:bg-stone-900"><h2 id="checkout-contact-heading" class="font-semibold"><span class="mr-2 inline-flex size-7 items-center justify-center rounded-full bg-stone-950 text-xs text-white dark:bg-white dark:text-stone-950">1</span>Contact information</h2>@if ($checkoutStep > 1)<button type="button" wire:click="editStep(1)" class="min-h-10 px-2 text-sm font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Edit</button>@endif</div>
                        @if ($checkoutStep === 1)
                            <div class="grid gap-4 p-5 sm:p-6"><div><label for="checkout-email" class="mb-1.5 block text-sm font-medium">Email address <span class="text-rose-700" aria-hidden="true">*</span></label><input id="checkout-email" type="email" wire:model.blur="email" autocomplete="email" required aria-describedby="checkout-email-error" class="storefront-input">@error('email')<p id="checkout-email-error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div><a href="/account/login?redirect=checkout" class="text-sm font-medium text-blue-800 underline underline-offset-4 dark:text-blue-300">Already have an account? Log in</a><button type="button" wire:click="continueToAddress" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-700 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-blue-400 dark:text-stone-950">Continue to shipping address</button></div>
                        @else
                            <p class="px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{{ data_get($checkout, 'email', $email ?? '') }}</p>
                        @endif
                    </section>

                    <section aria-labelledby="checkout-address-heading" class="overflow-hidden rounded-2xl border border-stone-200 dark:border-stone-800">
                        <div class="flex items-center justify-between gap-4 bg-stone-50 px-5 py-4 dark:bg-stone-900"><h2 id="checkout-address-heading" class="font-semibold"><span class="mr-2 inline-flex size-7 items-center justify-center rounded-full {{ $checkoutStep >= 2 ? 'bg-stone-950 text-white dark:bg-white dark:text-stone-950' : 'bg-stone-200 text-stone-500 dark:bg-stone-800' }} text-xs">2</span>Shipping address</h2>@if ($checkoutStep > 2)<button type="button" wire:click="editStep(2)" class="min-h-10 px-2 text-sm font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Edit</button>@endif</div>
                        @if ($checkoutStep === 2)
                            <div class="grid gap-5 p-5 sm:p-6">
                                @if (!empty($addresses ?? []))<div><label for="saved-address" class="mb-1.5 block text-sm font-medium">Select a saved address</label><select id="saved-address" wire:model.live="savedAddressId" class="storefront-input"><option value="">Use a new address</option>@foreach ($addresses as $address)<option value="{{ data_get($address, 'id') }}">{{ data_get($address, 'first_name') }} {{ data_get($address, 'last_name') }} · {{ data_get($address, 'address_line_1') }}, {{ data_get($address, 'city') }}</option>@endforeach</select></div>@endif
                                @include('storefront.components.address-form', ['address' => data_get($checkout, 'shipping_address', []), 'prefix' => 'shipping'])
                                <label class="flex min-h-11 items-center gap-3 text-sm"><input type="checkbox" wire:model.live="billingSameAsShipping" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700" checked>Billing address same as shipping</label>
                                @if (!($billingSameAsShipping ?? true))<div class="grid gap-3 border-t border-stone-200 pt-5 dark:border-stone-800"><h3 class="font-semibold">Billing address</h3>@include('storefront.components.address-form', ['address' => data_get($checkout, 'billing_address', []), 'prefix' => 'billing'])</div>@endif
                                <button type="button" wire:click="continueToShippingMethod" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-700 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-blue-400 dark:text-stone-950">Continue to shipping method</button>
                            </div>
                        @elseif ($checkoutStep > 2)
                            <div class="px-5 py-4 text-sm leading-6 text-stone-600 dark:text-stone-300">{{ data_get($checkout, 'shipping_address.first_name') }} {{ data_get($checkout, 'shipping_address.last_name') }}<br>{{ data_get($checkout, 'shipping_address.address_line_1') }}<br>{{ data_get($checkout, 'shipping_address.city') }}, {{ data_get($checkout, 'shipping_address.postal_code') }} · {{ data_get($checkout, 'shipping_address.country') }}</div>
                        @else
                            <p class="px-5 py-4 text-sm text-stone-500 dark:text-stone-400">Complete contact information to continue.</p>
                        @endif
                    </section>

                    <section aria-labelledby="checkout-shipping-heading" class="overflow-hidden rounded-2xl border border-stone-200 dark:border-stone-800">
                        <div class="flex items-center justify-between gap-4 bg-stone-50 px-5 py-4 dark:bg-stone-900"><h2 id="checkout-shipping-heading" class="font-semibold"><span class="mr-2 inline-flex size-7 items-center justify-center rounded-full {{ $checkoutStep >= 3 ? 'bg-stone-950 text-white dark:bg-white dark:text-stone-950' : 'bg-stone-200 text-stone-500 dark:bg-stone-800' }} text-xs">3</span>Shipping method</h2>@if ($checkoutStep > 3)<button type="button" wire:click="editStep(3)" class="min-h-10 px-2 text-sm font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Edit</button>@endif</div>
                        @if ($checkoutStep === 3)
                            <div class="grid gap-5 p-5 sm:p-6"><fieldset><legend class="sr-only">Choose a shipping method</legend>@forelse ($shippingRates as $rate)<label class="mb-3 flex min-h-[4.5rem] cursor-pointer items-center gap-3 rounded-xl border p-4 transition has-[:checked]:border-blue-700 has-[:checked]:bg-blue-50 focus-within:ring-2 focus-within:ring-blue-700 dark:border-stone-700 dark:has-[:checked]:border-blue-400 dark:has-[:checked]:bg-blue-950/40"><input type="radio" name="shipping_rate" value="{{ data_get($rate, 'id') }}" wire:model.live="shippingRateId" class="size-4 shrink-0 text-blue-700 focus:ring-blue-700"><span class="min-w-0 flex-1"><span class="block text-sm font-semibold">{{ data_get($rate, 'name', 'Shipping') }}</span><span class="mt-1 block text-xs text-stone-500 dark:text-stone-400">{{ data_get($rate, 'delivery_estimate', data_get($rate, 'description', 'Delivery estimate shown at checkout')) }}</span></span><span class="shrink-0 text-sm font-semibold">@include('storefront.components.price', ['amount' => (int) data_get($rate, 'amount', data_get($rate, 'price_amount', 0)), 'currency' => $checkoutCurrency])</span></label>@empty<div class="flex gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">@include('storefront.components.icon', ['name' => 'alert', 'class' => 'mt-0.5 size-5 shrink-0'])<p>No shipping methods are available for your address. Please verify your address or contact us.</p></div>@endforelse</fieldset>@error('shippingRateId')<p class="text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror<button type="button" wire:click="continueToPayment" @disabled(!is_countable($shippingRates) || count($shippingRates) === 0) class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-700 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-400 dark:text-stone-950">Continue to payment</button></div>
                        @elseif ($checkoutStep > 3)
                            <p class="px-5 py-4 text-sm text-stone-600 dark:text-stone-300">{{ data_get($checkout, 'shipping_rate.name', 'Shipping method selected') }}</p>
                        @else
                            <p class="px-5 py-4 text-sm text-stone-500 dark:text-stone-400">Complete your shipping address to see available methods.</p>
                        @endif
                    </section>

                    <section aria-labelledby="checkout-payment-heading" class="overflow-hidden rounded-2xl border border-stone-200 dark:border-stone-800">
                        <div class="bg-stone-50 px-5 py-4 dark:bg-stone-900"><h2 id="checkout-payment-heading" class="font-semibold"><span class="mr-2 inline-flex size-7 items-center justify-center rounded-full {{ $checkoutStep >= 4 ? 'bg-stone-950 text-white dark:bg-white dark:text-stone-950' : 'bg-stone-200 text-stone-500 dark:bg-stone-800' }} text-xs">4</span>Payment method</h2></div>
                        @if ($checkoutStep === 4)
                            <div class="p-5 sm:p-6">
                                <fieldset class="grid gap-3"><legend class="mb-3 text-sm text-stone-600 dark:text-stone-300">Select a payment method</legend>
                                    @foreach (['credit_card' => 'Credit card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank transfer'] as $method => $label)
                                        <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-stone-200 p-3 has-[:checked]:border-blue-700 has-[:checked]:bg-blue-50 dark:border-stone-700 dark:has-[:checked]:border-blue-400 dark:has-[:checked]:bg-blue-950/40"><input type="radio" name="payment_method" value="{{ $method }}" wire:model.live="paymentMethod" class="size-4 text-blue-700 focus:ring-blue-700"><span class="text-sm font-medium">{{ $label }}</span></label>
                                    @endforeach
                                </fieldset>
                                <div class="mt-5">
                                    @if ($selectedPaymentMethod === 'credit_card')
                                        <form wire:submit="pay" class="grid gap-4">
                                            <div><label for="card-number" class="mb-1.5 block text-sm font-medium">Card number <span class="text-rose-700" aria-hidden="true">*</span></label><input id="card-number" name="card_number" type="text" inputmode="numeric" autocomplete="cc-number" wire:model="cardNumber" placeholder="4242 4242 4242 4242" required aria-describedby="card-number-error" class="storefront-input">@error('cardNumber')<p id="card-number-error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div>
                                            <div><label for="cardholder-name" class="mb-1.5 block text-sm font-medium">Cardholder name <span class="text-rose-700" aria-hidden="true">*</span></label><input id="cardholder-name" type="text" autocomplete="cc-name" wire:model="cardholderName" required class="storefront-input"></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="card-expiry" class="mb-1.5 block text-sm font-medium">Expiry <span class="text-rose-700" aria-hidden="true">*</span></label><input id="card-expiry" type="text" inputmode="numeric" autocomplete="cc-exp" wire:model="expiry" placeholder="MM/YY" required class="storefront-input"></div><div><label for="card-cvc" class="mb-1.5 block text-sm font-medium">CVC <span class="text-rose-700" aria-hidden="true">*</span></label><input id="card-cvc" type="text" inputmode="numeric" autocomplete="cc-csc" wire:model="cvc" placeholder="123" required class="storefront-input"></div></div>
                                            @if ($paymentError ?? false)<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-100" role="alert">Payment declined: {{ $paymentError }}</div>@endif
                                            <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-blue-700 px-6 text-base font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:opacity-60 dark:bg-blue-400 dark:text-stone-950"><span wire:loading.remove wire:target="pay">Pay now · @include('storefront.components.price', ['amount' => $checkoutTotal, 'currency' => $checkoutCurrency])</span><span wire:loading wire:target="pay">Processing…</span></button>
                                        </form>
                                    @elseif ($selectedPaymentMethod === 'paypal')
                                        <form wire:submit="pay" class="grid gap-4"><p class="text-sm leading-6 text-stone-600 dark:text-stone-300">Your PayPal payment will be processed securely. You’ll stay on this page while your payment is completed.</p>@if ($paymentError ?? false)<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-100">Payment declined: {{ $paymentError }}</div>@endif<button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-blue-700 px-6 text-base font-semibold text-white hover:bg-blue-800 dark:bg-blue-400 dark:text-stone-950"><span wire:loading.remove wire:target="pay">Pay with PayPal · @include('storefront.components.price', ['amount' => $checkoutTotal, 'currency' => $checkoutCurrency])</span><span wire:loading wire:target="pay">Processing…</span></button></form>
                                    @else
                                        <form wire:submit="pay" class="grid gap-4"><div class="rounded-xl bg-stone-50 p-4 text-sm leading-6 text-stone-700 dark:bg-stone-900 dark:text-stone-300">After placing your order, you’ll receive bank transfer instructions. Your order will be held for 7 days while we await payment.</div>@if ($paymentError ?? false)<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-100">Payment declined: {{ $paymentError }}</div>@endif<button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-blue-700 px-6 text-base font-semibold text-white hover:bg-blue-800 dark:bg-blue-400 dark:text-stone-950"><span wire:loading.remove wire:target="pay">Place order · @include('storefront.components.price', ['amount' => $checkoutTotal, 'currency' => $checkoutCurrency])</span><span wire:loading wire:target="pay">Processing…</span></button></form>
                                    @endif
                                </div>
                            </div>
                        @else
                            <p class="px-5 py-4 text-sm text-stone-500 dark:text-stone-400">Complete the earlier steps to choose a payment method.</p>
                        @endif
                    </section>
                </div>
            </div>

            <div x-data="{ open: false }" class="min-w-0 lg:order-last">
                <button type="button" x-on:click="open = !open" class="flex min-h-14 w-full items-center justify-between gap-4 rounded-2xl bg-stone-100 px-4 text-left lg:hidden dark:bg-stone-900" aria-controls="checkout-summary-panel" x-bind:aria-expanded="open.toString()"><span class="inline-flex items-center gap-2 text-sm font-semibold">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-4'])<span x-text="open ? 'Hide order summary' : 'Show order summary'"></span></span><span class="text-sm font-semibold">@include('storefront.components.price', ['amount' => $checkoutTotal, 'currency' => $checkoutCurrency]) @include('storefront.components.icon', ['name' => 'chevron-down', 'class' => 'inline size-4'])</span></button>
                <div id="checkout-summary-panel" x-bind:class="open ? 'block' : 'hidden'" class="mt-3 lg:sticky lg:top-28 lg:mt-0 lg:block">
                    @include('storefront.components.order-summary', ['checkout' => $checkout, 'showDiscountInput' => true])
                </div>
            </div>
        </div>
    </section>
</div>
