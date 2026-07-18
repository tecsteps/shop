@php
    $totals = $checkout->totals_json;
    $currency = $totals['currency'] ?? $checkout->cart->currency;
    $requiresShipping = $this->requiresShipping;
@endphp

<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Checkout</h1>

    <!-- Mobile order summary toggle -->
    <div class="mt-4 lg:hidden">
        <button
            type="button"
            wire:click="$toggle('showOrderSummary')"
            class="flex w-full items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 text-sm font-medium dark:border-zinc-800"
        >
            <span class="flex items-center gap-2">
                <flux:icon name="{{ $showOrderSummary ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                {{ $showOrderSummary ? 'Hide order summary' : 'Show order summary' }}
            </span>
            @if ($totals)
                <x-storefront.price :amount="$totals['total']" :currency="$currency" />
            @endif
        </button>

        @if ($showOrderSummary)
            <div class="mt-3">
                @include('livewire.storefront.checkout.partials.summary')
            </div>
        @endif
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_380px]">
        <div class="space-y-4">
            <!-- Step 1: Contact & Shipping Address -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between px-6 py-4">
                    <h2 class="text-base font-semibold {{ $this->step === 1 ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 dark:text-zinc-600' }}">
                        1. Contact &amp; Shipping Address
                    </h2>
                    @if ($this->step > 1)
                        <button type="button" wire:click="editAddress" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                    @endif
                </div>

                @if ($this->step === 1)
                    <form wire:submit.prevent="saveAddress" class="space-y-4 border-t border-zinc-200 px-6 py-6 dark:border-zinc-800">
                        <flux:field>
                            <flux:label for="email">Email <span class="text-red-600">*</span></flux:label>
                            <flux:input id="email" type="email" wire:model.blur="email" autofocus required />
                            <flux:error name="email" />
                        </flux:field>
                        <p class="text-sm">
                            <a href="{{ route('storefront.account.login') }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">Already have an account? Log in</a>
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label for="firstName">First name <span class="text-red-600">*</span></flux:label>
                                <flux:input id="firstName" wire:model.blur="firstName" required />
                                <flux:error name="firstName" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="lastName">Last name <span class="text-red-600">*</span></flux:label>
                                <flux:input id="lastName" wire:model.blur="lastName" required />
                                <flux:error name="lastName" />
                            </flux:field>
                            <flux:field class="sm:col-span-2">
                                <flux:label for="address1">Address line 1 <span class="text-red-600">*</span></flux:label>
                                <flux:input id="address1" wire:model.blur="address1" required />
                                <flux:error name="address1" />
                            </flux:field>
                            <flux:field class="sm:col-span-2">
                                <flux:label for="address2">Address line 2</flux:label>
                                <flux:input id="address2" wire:model.blur="address2" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="city">City <span class="text-red-600">*</span></flux:label>
                                <flux:input id="city" wire:model.blur="city" required />
                                <flux:error name="city" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="province">State / Province</flux:label>
                                <flux:input id="province" wire:model.blur="province" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="postalCode">Postal code <span class="text-red-600">*</span></flux:label>
                                <flux:input id="postalCode" wire:model.blur="postalCode" required />
                                <flux:error name="postalCode" />
                            </flux:field>
                            <flux:field>
                                <flux:label for="country">Country <span class="text-red-600">*</span></flux:label>
                                <flux:select id="country" wire:model.blur="country" required>
                                    <option value="DE">Germany</option>
                                    <option value="AT">Austria</option>
                                    <option value="CH">Switzerland</option>
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="FR">France</option>
                                </flux:select>
                                <flux:error name="country" />
                            </flux:field>
                            <flux:field class="sm:col-span-2">
                                <flux:label for="phone">Phone</flux:label>
                                <flux:input id="phone" type="tel" wire:model.blur="phone" />
                            </flux:field>
                        </div>

                        @error('address')
                            <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
                        @enderror

                        <flux:button type="submit" variant="primary">Continue to shipping</flux:button>
                    </form>
                @elseif ($this->step > 1)
                    <div class="border-t border-zinc-200 px-6 py-4 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        <p>{{ $email }}</p>
                        <p>{{ $firstName }} {{ $lastName }}, {{ $address1 }}@if($address2), {{ $address2 }}@endif, {{ $postalCode }} {{ $city }}, {{ $country }}</p>
                    </div>
                @endif
            </div>

            <!-- Step 2: Shipping Method -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between px-6 py-4">
                    <h2 class="text-base font-semibold {{ $this->step === 2 ? 'text-zinc-900 dark:text-white' : ($this->step > 2 ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 dark:text-zinc-600') }}">
                        2. Shipping Method
                    </h2>
                    @if ($this->step > 2)
                        <button type="button" wire:click="editShipping" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                    @endif
                </div>

                @if ($this->step === 2)
                    <div class="border-t border-zinc-200 px-6 py-6 dark:border-zinc-800">
                        @error('shipping')
                            <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4">{{ $message }}</flux:callout>
                        @enderror

                        @if ($this->availableShippingRates->isEmpty())
                            <flux:callout variant="warning" icon="exclamation-triangle">
                                No shipping methods are available for your address. Please verify your address or contact us.
                            </flux:callout>
                        @else
                            <fieldset>
                                <legend class="sr-only">Shipping method</legend>
                                <div class="space-y-3">
                                    @foreach ($this->availableShippingRates as $rate)
                                        <label
                                            wire:key="rate-{{ $rate->id }}"
                                            class="flex cursor-pointer items-center justify-between rounded-lg border px-4 py-3 {{ $selectedShippingRateId === $rate->id ? 'border-blue-600 bg-blue-50 dark:bg-blue-950' : 'border-zinc-300 dark:border-zinc-700' }}"
                                        >
                                            <span class="flex items-center gap-3">
                                                <input
                                                    type="radio"
                                                    name="shipping_rate"
                                                    wire:click="selectShippingRate({{ $rate->id }})"
                                                    @checked($selectedShippingRateId === $rate->id)
                                                    class="text-blue-600"
                                                />
                                                <span>
                                                    <span class="block font-medium text-zinc-900 dark:text-white">{{ $rate->name }}</span>
                                                    @if ($rate->description)
                                                        <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ $rate->description }}</span>
                                                    @endif
                                                </span>
                                            </span>
                                            <span class="font-medium text-zinc-900 dark:text-white">
                                                @php $amount = app(\App\Services\ShippingCalculator::class)->calculate($rate, $checkout->cart); @endphp
                                                {{ $amount === 0 ? 'Free' : \App\Support\Money::format($amount ?? 0, $currency) }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif
                    </div>
                @elseif ($this->step > 2)
                    <div class="border-t border-zinc-200 px-6 py-4 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                        @if ($requiresShipping && $checkout->shippingMethod)
                            {{ $checkout->shippingMethod->name }}
                        @else
                            No shipping required
                        @endif
                    </div>
                @endif
            </div>

            <!-- Step 3: Payment -->
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800">
                <div class="px-6 py-4">
                    <h2 class="text-base font-semibold {{ $this->step === 3 ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 dark:text-zinc-600' }}">
                        3. Payment Method &amp; Pay
                    </h2>
                </div>

                @if ($this->step === 3)
                    <div class="border-t border-zinc-200 px-6 py-6 dark:border-zinc-800">
                        @if ($paymentError)
                            <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4">
                                Payment declined: {{ $paymentError }}
                            </flux:callout>
                        @endif

                        <fieldset class="space-y-3">
                            <legend class="mb-2 text-sm font-medium text-zinc-900 dark:text-white">Select a payment method</legend>

                            @foreach (['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'] as $value => $label)
                                <label wire:key="method-{{ $value }}" class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 {{ $selectedPaymentMethod === $value ? 'border-blue-600 bg-blue-50 dark:bg-blue-950' : 'border-zinc-300 dark:border-zinc-700' }}">
                                    <input type="radio" name="payment_method" wire:model.live="selectedPaymentMethod" value="{{ $value }}" class="text-blue-600" />
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $label }}</span>
                                </label>
                            @endforeach
                        </fieldset>

                        <form wire:submit.prevent="pay" class="mt-6 space-y-4">
                            @if ($selectedPaymentMethod === 'credit_card')
                                <flux:field>
                                    <flux:label for="cardNumber">Card number <span class="text-red-600">*</span></flux:label>
                                    <flux:input id="cardNumber" wire:model="cardNumber" placeholder="4242 4242 4242 4242" maxlength="16" required />
                                    <flux:error name="cardNumber" />
                                </flux:field>
                                <flux:field>
                                    <flux:label for="cardholderName">Cardholder name <span class="text-red-600">*</span></flux:label>
                                    <flux:input id="cardholderName" wire:model="cardholderName" required />
                                    <flux:error name="cardholderName" />
                                </flux:field>
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label for="cardExpiry">Expiry (MM/YY) <span class="text-red-600">*</span></flux:label>
                                        <flux:input id="cardExpiry" wire:model="cardExpiry" placeholder="12/28" required />
                                        <flux:error name="cardExpiry" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label for="cardCvc">CVC <span class="text-red-600">*</span></flux:label>
                                        <flux:input id="cardCvc" wire:model="cardCvc" placeholder="123" maxlength="4" required />
                                        <flux:error name="cardCvc" />
                                    </flux:field>
                                </div>
                            @elseif ($selectedPaymentMethod === 'paypal')
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">Your PayPal payment will be processed securely.</p>
                            @elseif ($selectedPaymentMethod === 'bank_transfer')
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.
                                </p>
                            @endif

                            <flux:button
                                type="submit"
                                variant="primary"
                                class="w-full"
                                wire:loading.attr="disabled"
                                wire:target="pay"
                            >
                                <span wire:loading.remove wire:target="pay">
                                    @if ($selectedPaymentMethod === 'credit_card')
                                        Pay now - <x-storefront.price :amount="$totals['total']" :currency="$currency" />
                                    @elseif ($selectedPaymentMethod === 'paypal')
                                        Pay with PayPal - <x-storefront.price :amount="$totals['total']" :currency="$currency" />
                                    @else
                                        Place order - <x-storefront.price :amount="$totals['total']" :currency="$currency" />
                                    @endif
                                </span>
                                <span wire:loading wire:target="pay">Processing...</span>
                            </flux:button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="hidden lg:block">
            <div class="sticky top-24">
                @include('livewire.storefront.checkout.partials.summary')
            </div>
        </div>
    </div>
</div>
