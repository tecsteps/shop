@php
    use App\Enums\CheckoutStatus;

    $summaryLines = array_map(fn (array $line): array => [
        'title' => $line['title'],
        'variant' => $line['variant_label'] !== '' ? $line['variant_label'] : null,
        'quantity' => $line['quantity'],
        'image_url' => $line['image_url'],
        'line_total_amount' => $line['line_total_amount'],
    ], $lines);

    $shippingKnown = in_array($checkout->status, [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected], true);
    $addressed = $checkout->status !== CheckoutStatus::Started;

    $stepHeaderClasses = 'flex items-center justify-between gap-4';
    $stepTitleClasses = 'text-base font-semibold text-zinc-900 dark:text-white';
    $futureTitleClasses = 'text-base font-semibold text-zinc-400 dark:text-zinc-600';
    $cardClasses = 'rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800';
    $editLinkClasses = 'text-sm font-medium text-blue-600 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-400';
    $primaryButtonClasses = 'rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600';
@endphp

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Checkout') }}</h1>

    <div class="mt-8 lg:grid lg:grid-cols-5 lg:items-start lg:gap-10">
        {{-- Form steps --}}
        <div class="space-y-4 lg:col-span-3">
            {{-- Step 1: Contact --}}
            <section class="{{ $cardClasses }}" aria-labelledby="step-contact">
                <div class="{{ $stepHeaderClasses }}">
                    <h2 id="step-contact" class="{{ $stepTitleClasses }}">1. {{ __('Contact information') }}</h2>
                    @if ($step > 1 && $step < 5)
                        <button type="button" wire:click="editStep(1)" class="{{ $editLinkClasses }}">{{ __('Edit') }}</button>
                    @endif
                </div>

                @if ($step === 1)
                    <form wire:submit="saveContact" class="mt-4 space-y-4">
                        <div>
                            <label for="checkout-email" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Email') }} <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="checkout-email"
                                type="email"
                                wire:model.blur="email"
                                required
                                autocomplete="email"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                @error('email') aria-invalid="true" aria-describedby="checkout-email-error" @enderror
                            />
                            @error('email')
                                <p id="checkout-email-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @guest('customer')
                                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Already have an account?') }}
                                    <a href="{{ route('storefront.account.login') }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ __('Log in') }}</a>
                                </p>
                            @endguest
                        </div>
                        <button type="submit" class="{{ $primaryButtonClasses }}" style="background-color: var(--sf-primary, #2563eb);">
                            {{ __('Continue') }}
                        </button>
                    </form>
                @elseif ($email !== '')
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $email }}</p>
                @endif
            </section>

            {{-- Step 2: Shipping address --}}
            <section class="{{ $cardClasses }}" aria-labelledby="step-address">
                <div class="{{ $stepHeaderClasses }}">
                    <h2 id="step-address" class="{{ $step >= 2 ? $stepTitleClasses : $futureTitleClasses }}">2. {{ __('Shipping address') }}</h2>
                    @if ($step > 2 && $step < 5)
                        <button type="button" wire:click="editStep(2)" class="{{ $editLinkClasses }}">{{ __('Edit') }}</button>
                    @endif
                </div>

                @if ($step === 2)
                    <form wire:submit="saveAddress" class="mt-4 space-y-4">
                        <x-storefront.address-form prefix="shipping" :address="$shipping" />
                        <button type="submit" class="{{ $primaryButtonClasses }}" style="background-color: var(--sf-primary, #2563eb);">
                            <span wire:loading.remove wire:target="saveAddress">{{ __('Continue') }}</span>
                            <span wire:loading wire:target="saveAddress">{{ __('Saving...') }}</span>
                        </button>
                    </form>
                @elseif ($step > 2 && $addressed)
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $shipping['first_name'] }} {{ $shipping['last_name'] }},
                        {{ $shipping['address1'] }}@if (filled($shipping['address2'] ?? '')), {{ $shipping['address2'] }}@endif,
                        {{ $shipping['postal_code'] }} {{ $shipping['city'] }}, {{ $shipping['country_code'] }}
                    </p>
                @endif
            </section>

            {{-- Step 3: Shipping method --}}
            <section class="{{ $cardClasses }}" aria-labelledby="step-shipping">
                <div class="{{ $stepHeaderClasses }}">
                    <h2 id="step-shipping" class="{{ $step >= 3 ? $stepTitleClasses : $futureTitleClasses }}">3. {{ __('Shipping method') }}</h2>
                    @if ($step > 3 && $step < 5 && $requiresShipping)
                        <button type="button" wire:click="editStep(3)" class="{{ $editLinkClasses }}">{{ __('Edit') }}</button>
                    @endif
                </div>

                @if ($step === 3)
                    <form wire:submit="saveShipping" class="mt-4 space-y-4">
                        @if ($availableRates === [])
                            <p class="flex items-start gap-2 text-sm text-amber-600 dark:text-amber-400">
                                <svg class="mt-0.5 size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                {{ __('No shipping methods are available for your address. Please verify your address or contact us.') }}
                            </p>
                        @else
                            <fieldset>
                                <legend class="sr-only">{{ __('Shipping method') }}</legend>
                                <div class="space-y-3">
                                    @foreach ($availableRates as $rate)
                                        <label
                                            wire:key="rate-{{ $rate['id'] }}"
                                            class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border p-4 transition {{ $selectedRateId === $rate['id'] ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-800 dark:hover:border-zinc-700' }}"
                                        >
                                            <span class="flex items-center gap-3">
                                                <input
                                                    type="radio"
                                                    wire:model.live="selectedRateId"
                                                    value="{{ $rate['id'] }}"
                                                    name="shipping-rate"
                                                    class="size-4 border-zinc-300 text-blue-600 focus:ring-blue-600"
                                                />
                                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $rate['name'] }}</span>
                                            </span>
                                            @if ($rate['amount'] === 0)
                                                <span class="text-sm font-semibold text-green-700 dark:text-green-400">{{ __('Free') }}</span>
                                            @else
                                                <x-storefront.price :amount="$rate['amount']" :currency="$currency" class="text-sm" />
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            @if ($shippingError !== null)
                                <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $shippingError }}</p>
                            @endif
                            <button
                                type="submit"
                                @disabled($selectedRateId === null)
                                class="{{ $primaryButtonClasses }} disabled:cursor-not-allowed disabled:opacity-50"
                                style="background-color: var(--sf-primary, #2563eb);"
                            >
                                {{ __('Continue') }}
                            </button>
                        @endif
                    </form>
                @elseif ($step > 3)
                    @php
                        $selectedRate = collect($availableRates)->firstWhere('id', $selectedRateId);
                    @endphp
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        @if (! $requiresShipping)
                            {{ __('No shipping required (digital order)') }}
                        @elseif ($selectedRate !== null)
                            {{ $selectedRate['name'] }}
                        @endif
                    </p>
                @endif
            </section>

            {{-- Step 4: Payment method --}}
            <section class="{{ $cardClasses }}" aria-labelledby="step-payment">
                <div class="{{ $stepHeaderClasses }}">
                    <h2 id="step-payment" class="{{ $step >= 4 ? $stepTitleClasses : $futureTitleClasses }}">4. {{ __('Payment') }}</h2>
                </div>

                @if ($step === 4)
                    <form wire:submit="selectPayment" class="mt-4 space-y-4">
                        <fieldset>
                            <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Select a payment method') }}</legend>
                            <div class="mt-3 space-y-3">
                                @foreach (['credit_card' => __('Credit Card'), 'paypal' => __('PayPal'), 'bank_transfer' => __('Bank Transfer')] as $method => $label)
                                    <label
                                        wire:key="payment-{{ $method }}"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition {{ $paymentMethod === $method ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-800 dark:hover:border-zinc-700' }}"
                                    >
                                        <input
                                            type="radio"
                                            wire:model.live="paymentMethod"
                                            value="{{ $method }}"
                                            name="payment-method"
                                            class="size-4 border-zinc-300 text-blue-600 focus:ring-blue-600"
                                        />
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @if ($paymentError !== null)
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $paymentError }}</p>
                        @endif
                        <button type="submit" class="{{ $primaryButtonClasses }}" style="background-color: var(--sf-primary, #2563eb);">
                            <span wire:loading.remove wire:target="selectPayment">{{ __('Continue to payment') }}</span>
                            <span wire:loading wire:target="selectPayment">{{ __('Processing...') }}</span>
                        </button>
                    </form>
                @elseif ($step === 5)
                    @php
                        $formattedTotal = \App\Support\Storefront\PriceFormatter::format($totals['total'] ?? 0, $currency);
                        $inputClasses = 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-white';
                        $labelClasses = 'mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300';
                    @endphp
                    <form wire:submit="payNow" class="mt-4 space-y-4">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Payment method:') }}
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ ['credit_card' => __('Credit Card'), 'paypal' => __('PayPal'), 'bank_transfer' => __('Bank Transfer')][$paymentMethod] ?? $paymentMethod }}
                            </span>
                        </p>

                        @if ($paymentMethod === 'credit_card')
                            <div class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <div>
                                    <label for="card-number" class="{{ $labelClasses }}">
                                        {{ __('Card number') }} <span class="text-red-600" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        id="card-number"
                                        type="text"
                                        wire:model="cardNumber"
                                        inputmode="numeric"
                                        autocomplete="cc-number"
                                        placeholder="4242 4242 4242 4242"
                                        required
                                        class="{{ $inputClasses }}"
                                        @error('cardNumber') aria-invalid="true" aria-describedby="card-number-error" @enderror
                                    />
                                    @error('cardNumber')
                                        <p id="card-number-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="card-name" class="{{ $labelClasses }}">
                                        {{ __('Cardholder name') }} <span class="text-red-600" aria-hidden="true">*</span>
                                    </label>
                                    <input
                                        id="card-name"
                                        type="text"
                                        wire:model="cardName"
                                        autocomplete="cc-name"
                                        required
                                        class="{{ $inputClasses }}"
                                        @error('cardName') aria-invalid="true" aria-describedby="card-name-error" @enderror
                                    />
                                    @error('cardName')
                                        <p id="card-name-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="card-expiry" class="{{ $labelClasses }}">
                                            {{ __('Expiry') }} <span class="text-red-600" aria-hidden="true">*</span>
                                        </label>
                                        <input
                                            id="card-expiry"
                                            type="text"
                                            wire:model="cardExpiry"
                                            placeholder="MM/YY"
                                            autocomplete="cc-exp"
                                            required
                                            class="{{ $inputClasses }}"
                                            @error('cardExpiry') aria-invalid="true" aria-describedby="card-expiry-error" @enderror
                                        />
                                        @error('cardExpiry')
                                            <p id="card-expiry-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="card-cvc" class="{{ $labelClasses }}">
                                            {{ __('CVC') }} <span class="text-red-600" aria-hidden="true">*</span>
                                        </label>
                                        <input
                                            id="card-cvc"
                                            type="text"
                                            wire:model="cardCvc"
                                            inputmode="numeric"
                                            placeholder="123"
                                            autocomplete="cc-csc"
                                            required
                                            class="{{ $inputClasses }}"
                                            @error('cardCvc') aria-invalid="true" aria-describedby="card-cvc-error" @enderror
                                        />
                                        @error('cardCvc')
                                            <p id="card-cvc-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @elseif ($paymentMethod === 'paypal')
                            <p class="rounded-xl border border-zinc-200 p-4 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                                {{ __('Your PayPal payment will be processed securely.') }}
                            </p>
                        @else
                            <p class="rounded-xl border border-zinc-200 p-4 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                                {{ __('After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.') }}
                            </p>
                        @endif

                        @if ($paymentError !== null)
                            <p class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-400" role="alert">
                                {{ $paymentError }}
                            </p>
                        @endif

                        <button
                            type="submit"
                            class="{{ $primaryButtonClasses }} w-full text-base"
                            style="background-color: var(--sf-primary, #2563eb);"
                        >
                            <span wire:loading.remove wire:target="payNow">
                                @if ($paymentMethod === 'paypal')
                                    {{ __('Pay with PayPal') }} - {{ $formattedTotal }}
                                @elseif ($paymentMethod === 'bank_transfer')
                                    {{ __('Place order') }} - {{ $formattedTotal }}
                                @else
                                    {{ __('Pay now') }} - {{ $formattedTotal }}
                                @endif
                            </span>
                            <span wire:loading wire:target="payNow">{{ __('Processing...') }}</span>
                        </button>
                    </form>
                @endif
            </section>
        </div>

        {{-- Order summary --}}
        <div class="mt-8 lg:col-span-2 lg:sticky lg:top-24 lg:mt-0">
            <x-storefront.order-summary
                :lines="$summaryLines"
                :currency="$currency"
                :subtotal-amount="$totals['subtotal'] ?? null"
                :discount-amount="$totals['discount'] ?? null"
                :discount-label="$checkout->discount_code"
                :shipping-amount="$shippingKnown ? ($totals['shipping'] ?? 0) : null"
                :tax-amount="$addressed ? ($totals['tax'] ?? 0) : null"
                :total-amount="$totals['total'] ?? null"
                :show-discount-input="false"
            />

            {{-- Discount code --}}
            <div class="mt-4 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900">
                @if (filled($checkout->discount_code))
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-green-700 dark:text-green-400">{{ $checkout->discount_code }}</span>
                        <button type="button" wire:click="removeDiscount" class="text-xs font-medium text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            {{ __('Remove') }}
                        </button>
                    </div>
                @else
                    <form wire:submit="applyDiscount" class="flex gap-2">
                        <label for="checkout-discount-code" class="sr-only">{{ __('Discount code') }}</label>
                        <input
                            id="checkout-discount-code"
                            type="text"
                            wire:model="discountCode"
                            placeholder="{{ __('Discount code') }}"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
                        />
                        <button type="submit" class="shrink-0 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            <span wire:loading.remove wire:target="applyDiscount">{{ __('Apply') }}</span>
                            <span wire:loading wire:target="applyDiscount">{{ __('Applying...') }}</span>
                        </button>
                    </form>
                    @if ($discountError !== null)
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $discountError }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
