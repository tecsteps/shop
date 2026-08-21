<div class="mx-auto max-w-7xl px-4 py-10 sm:py-14 lg:px-8">
    <div class="mx-auto max-w-3xl lg:mx-0">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Secure checkout</p>
        <h1 class="mt-2 text-4xl font-bold tracking-tight text-zinc-950 dark:text-white">Complete your order</h1>
        <p class="mt-3 text-zinc-600 dark:text-zinc-400">Your information is used only to process and deliver this order.</p>
    </div>

    <div class="mt-6 lg:hidden">
        <button type="button" wire:click="toggleOrderSummary" class="flex min-h-14 w-full items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-left dark:border-zinc-800 dark:bg-zinc-900" aria-controls="mobile-order-summary" aria-expanded="{{ $showOrderSummary ? 'true' : 'false' }}">
            <span>
                <span class="block text-sm font-semibold text-zinc-950 dark:text-white">{{ $showOrderSummary ? 'Hide order summary' : 'Show order summary' }}</span>
                <span class="mt-1 block text-sm text-zinc-600 dark:text-zinc-400">{{ $this->formatMoney($totals['total'] ?? 0, $totals['currency'] ?? null) }}</span>
            </span>
            <span class="text-xl text-zinc-500" aria-hidden="true">{{ $showOrderSummary ? '⌃' : '⌄' }}</span>
        </button>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start lg:gap-12">
        <main class="space-y-4" aria-label="Checkout steps">
            <section class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="checkout-step-contact">
                <div class="flex items-start justify-between gap-4 bg-zinc-50 px-5 py-4 dark:bg-zinc-900">
                    <button type="button" wire:click="setActiveStep(1)" class="flex min-w-0 items-start gap-3 text-left focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-zinc-900" aria-controls="checkout-contact-panel" aria-expanded="{{ $activeStep === 1 ? 'true' : 'false' }}">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $activeStep > 1 ? 'bg-green-600 text-white' : 'bg-blue-600 text-white' }} text-sm font-bold">{{ $activeStep > 1 ? '✓' : '1' }}</span>
                        <span>
                            <span id="checkout-step-contact" class="block font-semibold text-zinc-950 dark:text-white">Contact information</span>
                            @if ($activeStep > 1)
                                <span class="mt-1 block truncate text-sm font-normal text-zinc-600 dark:text-zinc-400">{{ $checkout->email }}</span>
                            @endif
                        </span>
                    </button>
                    @if ($activeStep > 1)
                        <button type="button" wire:click="setActiveStep(1)" class="shrink-0 text-sm font-semibold text-blue-600 underline underline-offset-4 focus:outline-none focus:ring-2 focus:ring-blue-600">Edit</button>
                    @endif
                </div>

                @if ($activeStep === 1)
                    <form id="checkout-contact-panel" wire:submit="saveContact" class="space-y-4 p-5 sm:p-6">
                        <div>
                            <label for="checkout-email" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Email address <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="checkout-email" type="email" wire:model.blur="email" autocomplete="email" required aria-describedby="checkout-email-help checkout-email-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-zinc-950 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <p id="checkout-email-help" class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">We’ll send your order confirmation here.</p>
                            @error('email')
                                <p id="checkout-email-error" class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Already have an account? <a href="{{ route('account.login') }}" class="font-semibold text-blue-600 underline underline-offset-4 focus:outline-none focus:ring-2 focus:ring-blue-600" wire:navigate>Log in</a></p>
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center rounded-full bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-zinc-950">
                            <span wire:loading.remove wire:target="saveContact">Continue to shipping address</span>
                            <span wire:loading wire:target="saveContact">Saving…</span>
                        </button>
                    </form>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="checkout-step-address">
                <div class="flex items-start justify-between gap-4 bg-zinc-50 px-5 py-4 dark:bg-zinc-900">
                    <button type="button" wire:click="setActiveStep(2)" @disabled($activeStep < 2) class="flex min-w-0 items-start gap-3 text-left focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-zinc-900" aria-controls="checkout-address-panel" aria-expanded="{{ $activeStep === 2 ? 'true' : 'false' }}">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $checkout->shipping_address_json !== null ? 'bg-green-600 text-white' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }} text-sm font-bold">{{ $checkout->shipping_address_json !== null && $activeStep !== 2 ? '✓' : '2' }}</span>
                        <span>
                            <span id="checkout-step-address" class="block font-semibold text-zinc-950 dark:text-white">Shipping address</span>
                            @if ($checkout->shipping_address_json !== null && $activeStep !== 2)
                                <span class="mt-1 block text-sm font-normal text-zinc-600 dark:text-zinc-400">{{ $shippingAddress['city'] }}, {{ $shippingAddress['country_code'] }}</span>
                            @endif
                        </span>
                    </button>
                    @if ($checkout->shipping_address_json !== null && $activeStep !== 2)
                        <button type="button" wire:click="setActiveStep(2)" class="shrink-0 text-sm font-semibold text-blue-600 underline underline-offset-4 focus:outline-none focus:ring-2 focus:ring-blue-600">Edit</button>
                    @endif
                </div>

                @if ($activeStep === 2)
                    <form id="checkout-address-panel" wire:submit="saveAddress" class="space-y-6 p-5 sm:p-6">
                        @if ($savedAddresses->isNotEmpty())
                            <div>
                                <label for="saved-address" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Use a saved address</label>
                                <select id="saved-address" wire:model.live="savedAddressId" wire:change="selectSavedAddress($event.target.value)" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-zinc-950 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    <option value="">Use a new address</option>
                                    @foreach ($savedAddresses as $address)
                                        <option wire:key="saved-address-{{ $address->id }}" value="{{ $address->id }}">{{ $address->label ?: 'Saved address' }}{{ $address->is_default ? ' (default)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <fieldset>
                            <legend class="text-base font-semibold text-zinc-950 dark:text-white">Shipping address</legend>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="shipping-first-name" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">First name <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="shipping-first-name" type="text" wire:model.blur="shippingAddress.first_name" autocomplete="given-name" required aria-describedby="shipping-first-name-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('shippingAddress.first_name')<p id="shipping-first-name-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="shipping-last-name" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Last name <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="shipping-last-name" type="text" wire:model.blur="shippingAddress.last_name" autocomplete="family-name" required aria-describedby="shipping-last-name-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('shippingAddress.last_name')<p id="shipping-last-name-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="shipping-address1" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Address line 1 <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="shipping-address1" type="text" wire:model.blur="shippingAddress.address1" autocomplete="address-line1" required aria-describedby="shipping-address1-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('shippingAddress.address1')<p id="shipping-address1-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="shipping-address2" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Address line 2 <span class="font-normal text-zinc-500">(optional)</span></label>
                                    <input id="shipping-address2" type="text" wire:model.blur="shippingAddress.address2" autocomplete="address-line2" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                </div>
                                <div>
                                    <label for="shipping-city" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">City <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="shipping-city" type="text" wire:model.blur="shippingAddress.city" autocomplete="address-level2" required aria-describedby="shipping-city-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('shippingAddress.city')<p id="shipping-city-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="shipping-state" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">State / province <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="shipping-state" type="text" wire:model.blur="shippingAddress.state" autocomplete="address-level1" required aria-describedby="shipping-state-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('shippingAddress.state')<p id="shipping-state-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="shipping-postal-code" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Postal code <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="shipping-postal-code" type="text" wire:model.blur="shippingAddress.postal_code" autocomplete="postal-code" required aria-describedby="shipping-postal-code-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('shippingAddress.postal_code')<p id="shipping-postal-code-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="shipping-country" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Country <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <select id="shipping-country" wire:model.blur="shippingAddress.country_code" autocomplete="country" required aria-describedby="shipping-country-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        <option value="DE">Germany</option>
                                        <option value="AT">Austria</option>
                                        <option value="CH">Switzerland</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="US">United States</option>
                                    </select>
                                    @error('shippingAddress.country_code')<p id="shipping-country-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="shipping-phone" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Phone <span class="font-normal text-zinc-500">(optional)</span></label>
                                    <input id="shipping-phone" type="tel" wire:model.blur="shippingAddress.phone" autocomplete="tel" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                </div>
                            </div>
                        </fieldset>

                        <label class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <input type="checkbox" wire:model.live="billingSameAsShipping" class="mt-1 h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-600 dark:border-zinc-700" />
                            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Billing address is the same as shipping</span>
                        </label>

                        @if (! $billingSameAsShipping)
                            <fieldset>
                                <legend class="text-base font-semibold text-zinc-950 dark:text-white">Billing address</legend>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="billing-first-name" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">First name <span class="text-red-600" aria-hidden="true">*</span></label>
                                        <input id="billing-first-name" type="text" wire:model.blur="billingAddress.first_name" autocomplete="billing given-name" required aria-describedby="billing-first-name-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        @error('billingAddress.first_name')<p id="billing-first-name-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="billing-last-name" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Last name <span class="text-red-600" aria-hidden="true">*</span></label>
                                        <input id="billing-last-name" type="text" wire:model.blur="billingAddress.last_name" autocomplete="billing family-name" required aria-describedby="billing-last-name-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        @error('billingAddress.last_name')<p id="billing-last-name-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="billing-address1" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Address line 1 <span class="text-red-600" aria-hidden="true">*</span></label>
                                        <input id="billing-address1" type="text" wire:model.blur="billingAddress.address1" autocomplete="billing address-line1" required aria-describedby="billing-address1-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        @error('billingAddress.address1')<p id="billing-address1-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="billing-address2" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Address line 2 <span class="font-normal text-zinc-500">(optional)</span></label>
                                        <input id="billing-address2" type="text" wire:model.blur="billingAddress.address2" autocomplete="billing address-line2" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    </div>
                                    <div>
                                        <label for="billing-city" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">City <span class="text-red-600" aria-hidden="true">*</span></label>
                                        <input id="billing-city" type="text" wire:model.blur="billingAddress.city" autocomplete="billing address-level2" required aria-describedby="billing-city-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        @error('billingAddress.city')<p id="billing-city-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="billing-state" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">State / province <span class="text-red-600" aria-hidden="true">*</span></label>
                                        <input id="billing-state" type="text" wire:model.blur="billingAddress.state" autocomplete="billing address-level1" required aria-describedby="billing-state-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        @error('billingAddress.state')<p id="billing-state-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="billing-postal-code" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Postal code <span class="text-red-600" aria-hidden="true">*</span></label>
                                        <input id="billing-postal-code" type="text" wire:model.blur="billingAddress.postal_code" autocomplete="billing postal-code" required aria-describedby="billing-postal-code-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        @error('billingAddress.postal_code')<p id="billing-postal-code-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="billing-country" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Country <span class="text-red-600" aria-hidden="true">*</span></label>
                                        <select id="billing-country" wire:model.blur="billingAddress.country_code" autocomplete="billing country" required aria-describedby="billing-country-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                            <option value="DE">Germany</option>
                                            <option value="AT">Austria</option>
                                            <option value="CH">Switzerland</option>
                                            <option value="GB">United Kingdom</option>
                                            <option value="US">United States</option>
                                        </select>
                                        @error('billingAddress.country_code')<p id="billing-country-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </fieldset>
                        @endif

                        <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center rounded-full bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-zinc-950">
                            <span wire:loading.remove wire:target="saveAddress">Continue to shipping</span>
                            <span wire:loading wire:target="saveAddress">Saving…</span>
                        </button>
                    </form>
                @elseif ($checkout->shipping_address_json === null)
                    <p class="p-5 text-sm text-zinc-600 dark:text-zinc-400 sm:p-6">Complete your contact information first.</p>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="checkout-step-shipping">
                <div class="flex items-start justify-between gap-4 bg-zinc-50 px-5 py-4 dark:bg-zinc-900">
                    <button type="button" wire:click="setActiveStep(3)" @disabled($activeStep < 3) class="flex min-w-0 items-start gap-3 text-left focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-zinc-900" aria-controls="checkout-shipping-panel" aria-expanded="{{ $activeStep === 3 ? 'true' : 'false' }}">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $checkout->shipping_rate_id !== null || ! $this->requiresShipping() ? 'bg-green-600 text-white' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }} text-sm font-bold">{{ ($checkout->shipping_rate_id !== null || ! $this->requiresShipping()) && $activeStep !== 3 ? '✓' : '3' }}</span>
                        <span>
                            <span id="checkout-step-shipping" class="block font-semibold text-zinc-950 dark:text-white">Shipping method</span>
                            @if (! $this->requiresShipping())
                                <span class="mt-1 block text-sm font-normal text-zinc-600 dark:text-zinc-400">No shipping required</span>
                            @elseif ($checkout->shippingRate && $activeStep !== 3)
                                <span class="mt-1 block text-sm font-normal text-zinc-600 dark:text-zinc-400">{{ $checkout->shippingRate->name }}</span>
                            @endif
                        </span>
                    </button>
                    @if ($activeStep > 3 && $this->requiresShipping())
                        <button type="button" wire:click="setActiveStep(3)" class="shrink-0 text-sm font-semibold text-blue-600 underline underline-offset-4 focus:outline-none focus:ring-2 focus:ring-blue-600">Edit</button>
                    @endif
                </div>

                @if ($activeStep === 3)
                    <form id="checkout-shipping-panel" wire:submit="chooseShipping" class="p-5 sm:p-6">
                        @if ($rates->isEmpty())
                            <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-200" role="alert">
                                <p class="font-semibold">No shipping methods are available for your address.</p>
                                <p class="mt-1">Please verify your address or contact us.</p>
                            </div>
                        @else
                            <fieldset>
                                <legend class="text-base font-semibold text-zinc-950 dark:text-white">Select a shipping method <span class="text-red-600" aria-hidden="true">*</span></legend>
                                <div class="mt-4 space-y-3">
                                    @foreach ($rates as $rate)
                                        <label wire:key="checkout-rate-{{ $rate->id }}" class="flex cursor-pointer items-center gap-4 rounded-xl border p-4 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-950/20 {{ (int) $shippingRateId === (int) $rate->id ? 'border-blue-600 bg-blue-50 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                            <input id="shipping-rate-{{ $rate->id }}" type="radio" wire:model="shippingRateId" value="{{ $rate->id }}" class="h-4 w-4 border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-600 dark:border-zinc-700">
                                            <span class="min-w-0 flex-1">
                                                <span class="block font-semibold text-zinc-950 dark:text-white">{{ $rate->name }}</span>
                                                <span class="mt-1 block text-sm text-zinc-600 dark:text-zinc-400">
                                                    @if ($rate->estimated_days_min && $rate->estimated_days_max)
                                                        {{ $rate->estimated_days_min }}–{{ $rate->estimated_days_max }} business days
                                                    @else
                                                        Delivery time shown at dispatch
                                                    @endif
                                                </span>
                                            </span>
                                            <span class="whitespace-nowrap text-sm font-semibold text-zinc-950 dark:text-white">{{ $this->formatMoney($rate->price_amount, $rate->currency) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            @error('shippingRateId')<p class="mt-3 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                            <button type="submit" wire:loading.attr="disabled" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-full bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-zinc-950">
                                <span wire:loading.remove wire:target="chooseShipping">Continue to payment</span>
                                <span wire:loading wire:target="chooseShipping">Saving…</span>
                            </button>
                        @endif
                    </form>
                @elseif ($checkout->shipping_address_json === null)
                    <p class="p-5 text-sm text-zinc-600 dark:text-zinc-400 sm:p-6">Complete your shipping address first.</p>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-labelledby="checkout-step-payment">
                <div class="flex items-start justify-between gap-4 bg-zinc-50 px-5 py-4 dark:bg-zinc-900">
                    <button type="button" wire:click="setActiveStep(4)" @disabled($activeStep < 4) class="flex min-w-0 items-start gap-3 text-left focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-zinc-900" aria-controls="checkout-payment-panel" aria-expanded="{{ $activeStep === 4 ? 'true' : 'false' }}">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $activeStep === 4 ? 'bg-blue-600 text-white' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }} text-sm font-bold">4</span>
                        <span>
                            <span id="checkout-step-payment" class="block font-semibold text-zinc-950 dark:text-white">Payment method</span>
                            @if ($checkout->payment_method)
                                <span class="mt-1 block text-sm font-normal text-zinc-600 dark:text-zinc-400">{{ ucfirst(str_replace('_', ' ', $checkout->payment_method)) }}</span>
                            @endif
                        </span>
                    </button>
                </div>

                @if ($activeStep === 4)
                    <form id="checkout-payment-panel" wire:submit="pay" class="p-5 sm:p-6">
                        <fieldset wire:loading.attr="disabled" wire:target="pay">
                            <legend class="text-base font-semibold text-zinc-950 dark:text-white">Select a payment method</legend>
                            <div class="mt-4 space-y-3">
                                @foreach (['credit_card' => 'Credit card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank transfer'] as $value => $label)
                                    <label wire:key="payment-method-{{ $value }}" class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-950/20 {{ $paymentMethod === $value ? 'border-blue-600 bg-blue-50 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                        <input id="payment-{{ $value }}" type="radio" wire:model.live="paymentMethod" value="{{ $value }}" class="h-4 w-4 border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-600 dark:border-zinc-700">
                                        <span class="font-semibold text-zinc-950 dark:text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @error('paymentMethod')<p class="mt-3 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror

                        @if ($paymentMethod === 'credit_card')
                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="card-number" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Card number <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="card-number" type="text" inputmode="numeric" wire:model.blur="cardNumber" autocomplete="cc-number" required aria-describedby="card-number-error" placeholder="4242 4242 4242 4242" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 tracking-wider focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('cardNumber')<p id="card-number-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="cardholder-name" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Cardholder name <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="cardholder-name" type="text" wire:model.blur="cardholderName" autocomplete="cc-name" required aria-describedby="cardholder-name-error" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('cardholderName')<p id="cardholder-name-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="card-expiry" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Expiry <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="card-expiry" type="text" inputmode="numeric" wire:model.blur="cardExpiry" autocomplete="cc-exp" required aria-describedby="card-expiry-error" placeholder="MM/YY" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('cardExpiry')<p id="card-expiry-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="card-cvc" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">CVC <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input id="card-cvc" type="text" inputmode="numeric" wire:model.blur="cardCvc" autocomplete="cc-csc" required aria-describedby="card-cvc-error" placeholder="123" class="mt-2 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('cardCvc')<p id="card-cvc-error" class="mt-1 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @elseif ($paymentMethod === 'paypal')
                            <p class="mt-6 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">Your PayPal payment will be processed securely onsite. No external redirect is required.</p>
                        @else
                            <p class="mt-6 rounded-lg bg-blue-50 p-4 text-sm text-blue-900 dark:bg-blue-950/40 dark:text-blue-200">After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.</p>
                        @endif

                        @error('payment')
                            <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $message }}</div>
                        @enderror
                        @if ($message)
                            <p class="mt-5 text-sm font-medium text-green-700 dark:text-green-300" role="status" aria-live="polite">{{ $message }}</p>
                        @endif

                        <button type="submit" wire:loading.attr="disabled" class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-blue-600 px-5 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-zinc-950">
                            <span wire:loading.remove wire:target="pay">
                                @if ($paymentMethod === 'paypal')
                                    Pay with PayPal - {{ $this->formatMoney($totals['total'] ?? 0, $totals['currency'] ?? null) }}
                                @elseif ($paymentMethod === 'bank_transfer')
                                    Place order - {{ $this->formatMoney($totals['total'] ?? 0, $totals['currency'] ?? null) }}
                                @else
                                    Pay now - {{ $this->formatMoney($totals['total'] ?? 0, $totals['currency'] ?? null) }}
                                @endif
                            </span>
                            <span wire:loading wire:target="pay">Processing…</span>
                        </button>
                    </form>
                @elseif ($activeStep < 4)
                    <p class="p-5 text-sm text-zinc-600 dark:text-zinc-400 sm:p-6">Choose your shipping method first.</p>
                @endif
            </section>
        </main>

        <aside id="mobile-order-summary" class="{{ $showOrderSummary ? '' : 'hidden' }} h-fit rounded-2xl bg-zinc-50 p-5 dark:bg-zinc-900 sm:p-6 lg:sticky lg:top-6 lg:block" aria-labelledby="order-summary-heading">
            <div class="flex items-center justify-between gap-4">
                <h2 id="order-summary-heading" class="text-xl font-semibold text-zinc-950 dark:text-white">Order summary</h2>
                <span class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ $checkout->cart->itemCount() }} {{ $checkout->cart->itemCount() === 1 ? 'item' : 'items' }}</span>
            </div>

            <div class="mt-6 divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($checkout->cart->lines as $line)
                    <div wire:key="checkout-summary-line-{{ $line->id }}" class="flex gap-3 py-4 first:pt-0 last:pb-0">
                        @if ($line->variant->product->media->first()?->url)
                            <img src="{{ $line->variant->product->media->first()->url }}" alt="{{ $line->variant->product->title }}" class="h-12 w-12 shrink-0 rounded-lg object-cover" loading="lazy">
                        @else
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white text-zinc-400 dark:bg-zinc-950" aria-hidden="true">⌂</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $line->variant->product->title }}</p>
                            <p class="mt-1 truncate text-xs text-zinc-600 dark:text-zinc-400">{{ $line->variant->title }} · Qty {{ $line->quantity }}</p>
                        </div>
                        <p class="whitespace-nowrap text-sm font-semibold text-zinc-950 dark:text-white">{{ $this->formatMoney($line->line_total_amount, $totals['currency'] ?? null) }}</p>
                    </div>
                @endforeach
            </div>

            <form wire:submit="applyDiscount" class="mt-6">
                <label for="checkout-discount" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Discount code</label>
                <div class="mt-2 flex gap-2">
                    <input id="checkout-discount" type="text" wire:model.blur="discountCode" autocomplete="off" placeholder="Enter code" aria-describedby="checkout-discount-error" class="min-h-11 min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 placeholder:text-zinc-500 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                    <button type="submit" wire:loading.attr="disabled" class="min-h-11 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:opacity-50 dark:border-zinc-700">Apply</button>
                </div>
            </form>
            @error('discountCode')<p id="checkout-discount-error" class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
            @if ($checkout->discount_code)
                <div class="mt-3 flex items-center justify-between gap-3 text-sm text-green-700 dark:text-green-300">
                    <span><span class="font-semibold">{{ $checkout->discount_code }}</span> applied</span>
                    <button type="button" wire:click="removeDiscount" class="font-medium underline underline-offset-4 focus:outline-none focus:ring-2 focus:ring-green-700">Remove</button>
                </div>
            @endif

            <dl class="mt-6 space-y-3 border-t border-zinc-200 pt-5 text-sm dark:border-zinc-700" aria-live="polite">
                <div class="flex justify-between gap-4"><dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt><dd class="font-medium text-zinc-950 dark:text-white">{{ $this->formatMoney($totals['subtotal'] ?? 0, $totals['currency'] ?? null) }}</dd></div>
                @if (($totals['discount'] ?? 0) > 0)
                    <div class="flex justify-between gap-4 text-green-700 dark:text-green-300"><dt>Discount</dt><dd class="font-medium">-{{ $this->formatMoney($totals['discount'], $totals['currency'] ?? null) }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt><dd class="text-right text-zinc-600 dark:text-zinc-400">{{ $checkout->shipping_rate_id ? $this->formatMoney($totals['shipping'] ?? 0, $totals['currency'] ?? null) : 'Calculated at next step' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-zinc-600 dark:text-zinc-400">Tax</dt><dd class="font-medium text-zinc-950 dark:text-white">{{ $this->formatMoney($totals['tax'] ?? 0, $totals['currency'] ?? null) }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-zinc-200 pt-4 text-lg font-bold dark:border-zinc-700"><dt class="text-zinc-950 dark:text-white">Total</dt><dd class="text-zinc-950 dark:text-white">{{ $this->formatMoney($totals['total'] ?? 0, $totals['currency'] ?? null) }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">Prices are shown in {{ $totals['currency'] ?? $checkout->cart->currency }}. Taxes are calculated from your address.</p>
        </aside>
    </div>
</div>
