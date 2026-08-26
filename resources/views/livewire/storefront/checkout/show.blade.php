<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Checkout</h1>

        @if ($this->expired)
            <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center dark:border-amber-900 dark:bg-amber-950/40">
                <p class="text-lg font-semibold text-amber-800 dark:text-amber-200">This checkout has expired</p>
                <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                    Your reserved items have been released. Please start a new checkout from your cart.
                </p>
                <a
                    href="{{ route('storefront.cart') }}"
                    class="mt-5 inline-flex items-center justify-center rounded-lg bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    Return to cart
                </a>
            </div>
        @else
            {{-- Mobile: collapsible order summary --}}
            <div x-data="{ summaryOpen: false }" class="mt-6 lg:hidden">
                <button
                    type="button"
                    @click="summaryOpen = !summaryOpen"
                    :aria-expanded="summaryOpen ? 'true' : 'false'"
                    class="flex w-full items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-zinc-50 px-5 py-4 text-sm font-medium text-zinc-900 transition hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800"
                >
                    <span>
                        <span x-text="summaryOpen ? 'Hide order summary' : 'Show order summary'">Show order summary</span>
                        <span class="ml-2 text-zinc-500 dark:text-zinc-400">({{ $this->checkout->cart->lines()->count() }} {{ $this->checkout->cart->lines()->count() === 1 ? 'item' : 'items' }})</span>
                    </span>
                    <span class="flex items-center gap-2 font-semibold">
                        <x-storefront-price :amount="$this->totals['total'] ?? 0" :currency="$this->currency" class="text-sm" />
                        <svg class="size-4 transition" :class="summaryOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </span>
                </button>
                <div x-show="summaryOpen" x-cloak class="mt-4">
                    <x-storefront-order-summary :checkout="$this->checkout" />
                </div>
            </div>

            <div class="mt-8 lg:grid lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start lg:gap-10">
                {{-- Steps --}}
                <div class="space-y-4">
                    {{-- Step 1: Contact --}}
                    <section class="rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="step-contact-heading">
                        <div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $this->currentStep === 1 ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : ($this->step1Complete ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400') }}">
                                    {{ $this->step1Complete ? '✓' : '1' }}
                                </span>
                                <h2 id="step-contact-heading" class="text-base font-semibold text-zinc-900 dark:text-white">Contact information</h2>
                            </div>
                            @if ($this->step1Complete && $this->currentStep !== 1)
                                <button type="button" wire:click="editStep(1)" class="text-sm font-medium text-blue-600 transition hover:underline dark:text-blue-400">
                                    Edit
                                </button>
                            @endif
                        </div>

                        @if ($this->currentStep === 1)
                            <div class="border-t border-zinc-100 px-5 py-5 sm:px-6 dark:border-zinc-800">
                                <label for="checkout-email" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Email <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="checkout-email"
                                    type="email"
                                    wire:model="email"
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                    required
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                                    aria-describedby="checkout-email-error"
                                />
                                @error('email')
                                    <p id="checkout-email-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror

                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    Already have an account?
                                    <a href="{{ route('account.login') }}" class="font-medium text-blue-600 transition hover:underline dark:text-blue-400">Log in</a>
                                </p>

                                <button
                                    type="button"
                                    wire:click="continueFromContact"
                                    class="mt-5 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
                                >
                                    Continue
                                </button>
                            </div>
                        @elseif ($this->step1Complete)
                            <div class="border-t border-zinc-100 px-5 py-4 text-sm text-zinc-700 sm:px-6 dark:border-zinc-800 dark:text-zinc-300">
                                {{ $this->email }}
                            </div>
                        @endif
                    </section>

                    {{-- Step 2: Shipping address --}}
                    <section class="rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="step-address-heading">
                        <div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $this->currentStep === 2 ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : ($this->step2Complete ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400') }}">
                                    {{ $this->step2Complete ? '✓' : '2' }}
                                </span>
                                <h2 id="step-address-heading" class="text-base font-semibold text-zinc-900 dark:text-white">Shipping address</h2>
                            </div>
                            @if ($this->step2Complete && $this->currentStep !== 2)
                                <button type="button" wire:click="editStep(2)" class="text-sm font-medium text-blue-600 transition hover:underline dark:text-blue-400">
                                    Edit
                                </button>
                            @endif
                        </div>

                        @if ($this->currentStep === 2)
                            <div class="border-t border-zinc-100 px-5 py-5 sm:px-6 dark:border-zinc-800">
                                @if ($this->savedAddresses !== [])
                                    <div class="mb-5">
                                        <label for="saved-address" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Select a saved address</label>
                                        <select
                                            id="saved-address"
                                            wire:change="loadSavedAddress($event.target.value)"
                                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-white dark:focus:ring-white/20"
                                        >
                                            <option value="">Use a new address</option>
                                            @foreach ($this->savedAddresses as $address)
                                                <option value="{{ $address['id'] }}">{{ $address['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <x-storefront-address-form :address="$this->shipping" prefix="shipping" />

                                <div class="mt-6">
                                    <label class="flex items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                                        <input
                                            type="checkbox"
                                            wire:model="billingSameAsShipping"
                                            class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:ring-offset-zinc-950"
                                        />
                                        Billing address same as shipping
                                    </label>
                                </div>

                                @if (! $this->billingSameAsShipping)
                                    <div class="mt-6 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                                        <h3 class="mb-4 text-sm font-semibold text-zinc-900 dark:text-white">Billing address</h3>
                                        <x-storefront-address-form :address="$this->billing" prefix="billing" :show-phone="false" />
                                    </div>
                                @endif

                                <button
                                    type="button"
                                    wire:click="continueFromAddress"
                                    class="mt-6 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
                                >
                                    Continue
                                </button>
                            </div>
                        @elseif ($this->step2Complete)
                            <div class="border-t border-zinc-100 px-5 py-4 text-sm text-zinc-700 sm:px-6 dark:border-zinc-800 dark:text-zinc-300">
                                <p>{{ $this->shipping['first_name'] ?? '' }} {{ $this->shipping['last_name'] ?? '' }}</p>
                                <p>{{ $this->shipping['address1'] ?? '' }}</p>
                                @if (! empty($this->shipping['address2']))
                                    <p>{{ $this->shipping['address2'] }}</p>
                                @endif
                                <p>{{ $this->shipping['city'] ?? '' }}{{ ! empty($this->shipping['province']) ? ', '.$this->shipping['province'] : '' }} {{ $this->shipping['postal_code'] ?? '' }}</p>
                                <p>{{ $this->shipping['country'] ?? '' }}</p>
                            </div>
                        @endif
                    </section>

                    {{-- Step 3: Shipping method --}}
                    <section class="rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="step-shipping-heading">
                        <div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $this->currentStep === 3 ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : ($this->step3Complete ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400') }}">
                                    {{ $this->step3Complete ? '✓' : '3' }}
                                </span>
                                <h2 id="step-shipping-heading" class="text-base font-semibold text-zinc-900 dark:text-white">Shipping method</h2>
                            </div>
                        </div>

                        @if ($this->currentStep === 3)
                            <div class="border-t border-zinc-100 px-5 py-5 sm:px-6 dark:border-zinc-800">
                                @if ($this->shippingMethods->isEmpty())
                                    <p class="flex items-start gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                                        </svg>
                                        No shipping methods are available for your address. Please verify your address or contact us.
                                    </p>
                                @else
                                    <fieldset>
                                        <legend class="sr-only">Shipping method</legend>
                                        <div class="space-y-3">
                                            @foreach ($this->shippingMethods as $rate)
                                                <label
                                                    class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border p-4 transition {{ (int) $this->shippingMethodId === $rate->id ? 'border-blue-600 bg-blue-50/50 dark:border-blue-500 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                                                >
                                                    <span class="flex items-center gap-3">
                                                        <input
                                                            type="radio"
                                                            name="shipping_method"
                                                            value="{{ $rate->id }}"
                                                            wire:model.live="shippingMethodId"
                                                            class="size-4 border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:ring-offset-zinc-950"
                                                        />
                                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $rate->name }}</span>
                                                    </span>
                                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                                        <x-storefront-price :amount="$rate->amount" :currency="$this->currency" />
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>

                                    <button
                                        type="button"
                                        wire:click="continueFromShipping"
                                        class="mt-6 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
                                    >
                                        Continue
                                    </button>
                                    @error('shippingMethodId')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">Please choose a shipping method.</p>
                                    @enderror
                                @endif
                            </div>
                        @elseif ($this->step3Complete)
                            <div class="border-t border-zinc-100 px-5 py-4 text-sm text-zinc-700 sm:px-6 dark:border-zinc-800 dark:text-zinc-300">
                                @php
                                    $selectedRate = $this->shippingMethods->firstWhere('id', $this->checkout->shipping_method_id);
                                @endphp
                                @if ($selectedRate)
                                    <div class="flex items-center justify-between gap-4">
                                        <span>{{ $selectedRate->name }}</span>
                                        <x-storefront-price :amount="$selectedRate->amount" :currency="$this->currency" class="text-sm" />
                                    </div>
                                @else
                                    Shipping method selected
                                @endif
                            </div>
                        @endif
                    </section>

                    {{-- Step 4: Payment --}}
                    <section class="rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="step-payment-heading">
                        <div class="flex items-center gap-3 px-5 py-4 sm:px-6">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $this->currentStep === 4 ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                4
                            </span>
                            <h2 id="step-payment-heading" class="text-base font-semibold text-zinc-900 dark:text-white">Payment</h2>
                        </div>

                        @if ($this->currentStep === 4)
                            <div class="border-t border-zinc-100 px-5 py-5 sm:px-6 dark:border-zinc-800">
                                @if ($this->payError)
                                    <p class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300" role="alert">
                                        {{ $this->payError }}
                                    </p>
                                @endif

                                <fieldset>
                                    <legend class="text-sm font-semibold text-zinc-900 dark:text-white">Select a payment method</legend>
                                    <div class="mt-3 space-y-3">
                                        @foreach (['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'] as $method => $label)
                                            <label
                                                class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition {{ $this->paymentMethod === $method ? 'border-blue-600 bg-blue-50/50 dark:border-blue-500 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                                            >
                                                <input
                                                    type="radio"
                                                    name="payment_method"
                                                    value="{{ $method }}"
                                                    wire:model.live="paymentMethod"
                                                    class="size-4 border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:ring-offset-zinc-950"
                                                />
                                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                {{-- Credit card form --}}
                                @if ($this->paymentMethod === 'credit_card')
                                    <div class="mt-5 space-y-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                                        <div>
                                            <label for="card-number" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Card number</label>
                                            <input
                                                id="card-number"
                                                type="text"
                                                inputmode="numeric"
                                                autocomplete="cc-number"
                                                placeholder="4242 4242 4242 4242"
                                                wire:model.live="cardNumber"
                                                maxlength="19"
                                                x-on:input="this.value = this.value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim()"
                                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                                                aria-describedby="card-number-error"
                                            />
                                            @error('cardNumber')
                                                <p id="card-number-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="card-holder" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Cardholder name</label>
                                            <input
                                                id="card-holder"
                                                type="text"
                                                autocomplete="cc-name"
                                                placeholder="John Doe"
                                                wire:model.live="cardHolder"
                                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                                                aria-describedby="card-holder-error"
                                            />
                                            @error('cardHolder')
                                                <p id="card-holder-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="card-expiry" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Expiry</label>
                                                <input
                                                    id="card-expiry"
                                                    type="text"
                                                    inputmode="numeric"
                                                    autocomplete="cc-exp"
                                                    placeholder="MM/YY"
                                                    wire:model.live="cardExpiry"
                                                    maxlength="5"
                                                    x-on:input="this.value = this.value.replace(/[^\d]/g, '').slice(0, 4).replace(/(\d{2})(\d{0,2})/, '$1/$2')"
                                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                                                    aria-describedby="card-expiry-error"
                                                />
                                                @error('cardExpiry')
                                                    <p id="card-expiry-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="card-cvc" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">CVC</label>
                                                <input
                                                    id="card-cvc"
                                                    type="text"
                                                    inputmode="numeric"
                                                    autocomplete="cc-csc"
                                                    placeholder="123"
                                                    wire:model.live="cardCvc"
                                                    maxlength="4"
                                                    x-on:input="this.value = this.value.replace(/\D/g, '').slice(0, 4)"
                                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                                                    aria-describedby="card-cvc-error"
                                                />
                                                @error('cardCvc')
                                                    <p id="card-cvc-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @elseif ($this->paymentMethod === 'paypal')
                                    <p class="mt-5 rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                                        Your PayPal payment will be processed securely.
                                    </p>
                                @else
                                    <p class="mt-5 rounded-xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                                        After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.
                                    </p>
                                @endif

                                @php
                                    $total = $this->totals['total'] ?? 0;
                                    $buttonLabel = match ($this->paymentMethod) {
                                        'credit_card' => 'Pay now',
                                        'paypal' => 'Pay with PayPal',
                                        default => 'Place order',
                                    };
                                @endphp
                                <button
                                    type="button"
                                    wire:click="pay"
                                    wire:loading.attr="disabled"
                                    class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-4 text-base font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-500 dark:hover:bg-blue-400"
                                >
                                    <span wire:loading.remove wire:target="pay">{{ $buttonLabel }} - <x-storefront-price :amount="$total" :currency="$this->currency" /></span>
                                    <span wire:loading wire:target="pay" class="inline-flex items-center gap-2">
                                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                                        </svg>
                                        Processing...
                                    </span>
                                </button>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- Desktop order summary --}}
                <aside class="sticky top-24 hidden lg:block" aria-label="Order summary">
                    <x-storefront-order-summary :checkout="$this->checkout" />
                </aside>
            </div>
        @endif
    </div>
</div>
