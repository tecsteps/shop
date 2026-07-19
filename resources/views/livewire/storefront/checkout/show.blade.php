<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    @if ($expired)
        {{-- Expired checkout (410-style) --}}
        <div class="mx-auto max-w-lg py-16 text-center">
            <svg class="mx-auto size-16 text-gray-300 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">This checkout has expired</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Checkouts are held for 24 hours. Your cart is still saved — start a new checkout when you're ready.</p>
            <a href="{{ route('storefront.cart.show') }}"
               class="mt-6 inline-block rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                Return to cart
            </a>
        </div>
    @else
        @php
            $totals = $checkout?->totals_json ?? [];
            $summaryLines = $checkout?->cart?->lines ?? $previewCart?->lines ?? collect();
            $summaryCurrency = $checkout?->cart?->currency ?? $previewCart?->currency ?? app('current_store')->default_currency;
            $summarySubtotal = $checkout !== null ? ($totals['subtotal'] ?? 0) : ($previewCart?->subtotal() ?? 0);
            $summaryDiscount = $totals['discount'] ?? 0;
            $summaryShipping = $totals['shipping'] ?? null;
            $summaryTax = $totals['tax'] ?? 0;
            $summaryTotal = $checkout !== null ? ($totals['total'] ?? 0) : $summarySubtotal;
        @endphp

        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Checkout</h1>

        <div class="mt-8 grid grid-cols-1 gap-10 lg:grid-cols-5">
            {{-- Steps --}}
            <div class="space-y-6 lg:col-span-3">
                {{-- Step 1: Contact & shipping address --}}
                <section aria-labelledby="checkout-step-1" class="rounded-lg border {{ $step === 1 ? 'border-blue-500' : 'border-gray-200 dark:border-gray-800' }} bg-white dark:bg-gray-950">
                    <h2 id="checkout-step-1" class="flex items-center justify-between border-b border-gray-200 px-4 py-3 text-sm font-semibold dark:border-gray-800">
                        <span class="{{ $step >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">1. Contact &amp; shipping address</span>
                        @if ($step > 1)
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $email }}</span>
                        @endif
                    </h2>

                    @if ($step === 1)
                        <form wire:submit="submitAddress" class="space-y-4 px-4 py-4">
                            <div>
                                <label for="checkout-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span aria-hidden="true" class="text-red-500">*</span></label>
                                <input id="checkout-email" type="email" wire:model="email" required
                                       class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                       aria-describedby="checkout-email-error">
                                @error('email') <p id="checkout-email-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ([
                                    'first_name' => ['label' => 'First name', 'required' => true],
                                    'last_name' => ['label' => 'Last name', 'required' => true],
                                    'address1' => ['label' => 'Address line 1', 'required' => true, 'full' => true],
                                    'address2' => ['label' => 'Address line 2 (optional)', 'required' => false, 'full' => true],
                                    'city' => ['label' => 'City', 'required' => true],
                                    'province' => ['label' => 'State / Province (optional)', 'required' => false],
                                    'postal_code' => ['label' => 'Postal code', 'required' => true],
                                    'country_code' => ['label' => 'Country code (e.g. DE)', 'required' => true],
                                    'phone' => ['label' => 'Phone (optional)', 'required' => false, 'full' => true],
                                ] as $field => $options)
                                    <div class="{{ ! empty($options['full']) ? 'sm:col-span-2' : '' }}">
                                        <label for="address-{{ $field }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $options['label'] }} @if ($options['required']) <span aria-hidden="true" class="text-red-500">*</span> @endif
                                        </label>
                                        <input id="address-{{ $field }}" type="text" wire:model="address.{{ $field }}" @if ($options['required']) required @endif
                                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                               aria-describedby="address-{{ $field }}-error">
                                        @error('address.'.$field) <p id="address-{{ $field }}-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>

                            @error('address.country') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            @error('shipping_address') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="useShippingAsBilling" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900">
                                Billing address same as shipping
                            </label>

                            <button type="submit"
                                    class="w-full rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 sm:w-auto"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitAddress">Continue to shipping</span>
                                <span wire:loading wire:target="submitAddress">Saving...</span>
                            </button>
                        </form>
                    @elseif ($checkout !== null && ! empty($checkout->shipping_address_json))
                        <div class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $checkout->shipping_address_json['first_name'] ?? '' }} {{ $checkout->shipping_address_json['last_name'] ?? '' }},
                            {{ $checkout->shipping_address_json['address1'] ?? '' }},
                            {{ $checkout->shipping_address_json['postal_code'] ?? '' }} {{ $checkout->shipping_address_json['city'] ?? '' }},
                            {{ $checkout->shipping_address_json['country_code'] ?? '' }}
                        </div>
                    @endif
                </section>

                {{-- Step 2: Shipping method --}}
                <section aria-labelledby="checkout-step-2" class="rounded-lg border {{ $step === 2 ? 'border-blue-500' : 'border-gray-200 dark:border-gray-800' }} bg-white dark:bg-gray-950">
                    <h2 id="checkout-step-2" class="border-b border-gray-200 px-4 py-3 text-sm font-semibold dark:border-gray-800">
                        <span class="{{ $step >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600' }}">2. Shipping method</span>
                    </h2>

                    @if ($step === 2 && $checkout !== null)
                        @if (! $checkout->requiresShipping())
                            <div class="space-y-4 px-4 py-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">This order does not require shipping.</p>
                                <button type="button" wire:click="continueWithoutShipping"
                                        class="rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                                    Continue to payment
                                </button>
                            </div>
                        @elseif ($rates->isEmpty())
                            <div class="px-4 py-4">
                                <p class="flex items-center gap-2 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    No shipping methods are available for your address. Please verify your address or contact us.
                                </p>
                            </div>
                        @else
                            <fieldset class="space-y-3 px-4 py-4">
                                <legend class="sr-only">Available shipping methods</legend>
                                @foreach ($rates as $rate)
                                    <button type="button" wire:click="selectShipping({{ $rate->id }})"
                                            class="flex w-full items-center justify-between gap-4 rounded-md border border-gray-300 px-4 py-3 text-left hover:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rate->name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            @if ($rate->estimatedDaysMin !== null && $rate->estimatedDaysMax !== null)
                                                {{ $rate->estimatedDaysMin }}-{{ $rate->estimatedDaysMax }} business days
                                            @endif
                                        </span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $rate->amount > 0 ? \App\Support\Money::format($rate->amount, $summaryCurrency) : 'Free' }}
                                        </span>
                                    </button>
                                @endforeach
                                @error('shippingMethodId') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </fieldset>
                        @endif
                    @elseif ($step > 2 && $checkout !== null)
                        <div class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $checkout->requiresShipping() ? 'Shipping method selected' : 'No shipping required' }}
                        </div>
                    @endif
                </section>

                {{-- Step 3: Payment method --}}
                <section aria-labelledby="checkout-step-3" class="rounded-lg border {{ $step === 3 ? 'border-blue-500' : 'border-gray-200 dark:border-gray-800' }} bg-white dark:bg-gray-950">
                    <h2 id="checkout-step-3" class="border-b border-gray-200 px-4 py-3 text-sm font-semibold dark:border-gray-800">
                        <span class="{{ $step >= 3 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600' }}">3. Payment</span>
                    </h2>

                    @if ($step === 3)
                        <div class="space-y-4 px-4 py-4">
                            <fieldset class="space-y-3">
                                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">Select a payment method</legend>
                                @foreach (['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'] as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-md border px-4 py-3 {{ $paymentMethod === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-gray-300 dark:border-gray-700' }} {{ $paymentSelected ? 'opacity-60' : '' }}">
                                        <input type="radio" wire:model.live="paymentMethod" value="{{ $value }}" @if ($paymentSelected) disabled @endif class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </fieldset>

                            @if (! $paymentSelected)
                                <button type="button" wire:click="selectPayment"
                                        class="rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="selectPayment">Continue</span>
                                    <span wire:loading wire:target="selectPayment">Reserving...</span>
                                </button>
                            @else
                                @php $storedMethod = $checkout?->payment_method?->value ?? $paymentMethod; @endphp

                                <form wire:submit="pay" class="space-y-4">
                                    @if ($storedMethod === 'credit_card')
                                        <div>
                                            <label for="card-number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Card number <span aria-hidden="true" class="text-red-500">*</span></label>
                                            <input id="card-number" type="text" inputmode="numeric" wire:model="cardNumber" placeholder="4242 4242 4242 4242" required
                                                   class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                   aria-describedby="card-number-error">
                                            @error('cardNumber') <p id="card-number-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="card-holder" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cardholder name <span aria-hidden="true" class="text-red-500">*</span></label>
                                            <input id="card-holder" type="text" wire:model="cardHolder" required
                                                   class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                   aria-describedby="card-holder-error">
                                            @error('cardHolder') <p id="card-holder-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="card-expiry" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Expiry (MM/YY) <span aria-hidden="true" class="text-red-500">*</span></label>
                                                <input id="card-expiry" type="text" inputmode="numeric" wire:model="cardExpiry" placeholder="12/28" required
                                                       class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                       aria-describedby="card-expiry-error">
                                                @error('cardExpiry') <p id="card-expiry-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label for="card-cvc" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CVC <span aria-hidden="true" class="text-red-500">*</span></label>
                                                <input id="card-cvc" type="text" inputmode="numeric" wire:model="cardCvc" placeholder="123" required
                                                       class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                       aria-describedby="card-cvc-error">
                                                @error('cardCvc') <p id="card-cvc-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    @elseif ($storedMethod === 'paypal')
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Your PayPal payment will be processed securely.</p>
                                    @else
                                        <p class="text-sm text-gray-600 dark:text-gray-400">After placing your order, you will receive bank transfer instructions. Your order will be held while we await your payment.</p>
                                    @endif

                                    @if ($paymentError !== null)
                                        <p role="alert" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">{{ $paymentError }}</p>
                                    @endif

                                    <button type="submit"
                                            class="w-full rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                                            wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="pay">
                                            @if ($storedMethod === 'credit_card')
                                                Pay now - {{ \App\Support\Money::format($summaryTotal, $summaryCurrency) }}
                                            @elseif ($storedMethod === 'paypal')
                                                Pay with PayPal - {{ \App\Support\Money::format($summaryTotal, $summaryCurrency) }}
                                            @else
                                                Place order - {{ \App\Support\Money::format($summaryTotal, $summaryCurrency) }}
                                            @endif
                                        </span>
                                        <span wire:loading wire:target="pay">Processing...</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </section>
            </div>

            {{-- Order summary (spec 04 §8.3) --}}
            <aside class="lg:col-span-2" aria-label="Order summary">
                <div class="rounded-lg bg-gray-50 p-6 lg:sticky lg:top-20 dark:bg-gray-900">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Order Summary</h2>

                    <ul class="mt-4 space-y-3">
                        @foreach ($summaryLines as $line)
                            <li wire:key="summary-line-{{ $line->id }}" class="flex items-center gap-3">
                                @php $image = $line->variant?->product?->media->first(); @endphp
                                @if ($image !== null)
                                    <img src="{{ $image->url() }}" alt="" class="size-12 shrink-0 rounded-md object-cover">
                                @else
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-md bg-gray-200 dark:bg-gray-800" aria-hidden="true">
                                        <svg class="size-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $line->variant?->product?->title }} <span class="text-gray-500">×{{ $line->quantity }}</span></p>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $line->variant?->title() }}</p>
                                </div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($line->line_total_amount, $summaryCurrency) }}</p>
                            </li>
                        @endforeach
                    </ul>

                    @if ($checkout !== null)
                        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                            @if ($checkout->discount_code !== null)
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-green-600 dark:text-green-400">{{ $checkout->discount_code }}</p>
                                    <button type="button" wire:click="removeDiscount" class="text-xs font-medium text-gray-500 underline hover:text-gray-700 focus:outline-hidden rounded dark:text-gray-400 dark:hover:text-gray-200">Remove</button>
                                </div>
                            @else
                                <form wire:submit="applyDiscount" class="flex gap-2">
                                    <label for="checkout-discount-code" class="sr-only">Discount code</label>
                                    <input id="checkout-discount-code" type="text" wire:model="discountCode" placeholder="Discount code"
                                           class="min-w-0 flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                                    <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Apply</button>
                                </form>
                                @if ($discountError !== null)
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $discountError }}</p>
                                @endif
                            @endif
                        </div>
                    @endif

                    <dl class="mt-4 space-y-2 border-t border-gray-200 pt-4 dark:border-gray-800" aria-live="polite">
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-600 dark:text-gray-400">Subtotal</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($summarySubtotal, $summaryCurrency) }}</dd>
                        </div>
                        @if ($summaryDiscount > 0)
                            <div class="flex items-center justify-between text-sm text-green-600 dark:text-green-400">
                                <dt>Discount</dt>
                                <dd>-{{ \App\Support\Money::format($summaryDiscount, $summaryCurrency) }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-600 dark:text-gray-400">Shipping</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $summaryShipping !== null ? \App\Support\Money::format($summaryShipping, $summaryCurrency) : 'Calculated at next step' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-gray-600 dark:text-gray-400">Tax</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($summaryTax, $summaryCurrency) }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold dark:border-gray-800">
                            <dt class="text-gray-900 dark:text-white">Total</dt>
                            <dd class="text-gray-900 dark:text-white">{{ \App\Support\Money::format($summaryTotal, $summaryCurrency) }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    @endif
</div>
