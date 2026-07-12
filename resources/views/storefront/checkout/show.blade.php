<div class="sf-container sf-page-y max-w-7xl" x-data="{ summaryOpen: @entangle('summaryOpen').live }" @checkout-step-changed.window="$nextTick(() => document.querySelector('[data-current-step] input, [data-current-step] button, [data-current-step] select')?.focus())">
    <div class="mb-8 flex items-center justify-between gap-5">
        <a href="{{ url('/') }}" wire:navigate class="text-xl font-semibold tracking-tight">{{ $currentStore->name }}</a>
        <span class="inline-flex items-center gap-2 text-sm text-slate-500"><svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.5 8V6a4.5 4.5 0 1 1 9 0v2h.25A2.25 2.25 0 0 1 17 10.25v6.5A2.25 2.25 0 0 1 14.75 19h-9.5A2.25 2.25 0 0 1 3 16.75v-6.5A2.25 2.25 0 0 1 5.25 8h.25Zm7.5 0V6a3 3 0 1 0-6 0v2h6Z" clip-rule="evenodd"/></svg> Secure checkout</span>
    </div>

    <button type="button" class="mb-6 flex w-full items-center justify-between rounded-2xl bg-slate-100 p-4 text-left dark:bg-slate-900 lg:hidden" @click="summaryOpen = !summaryOpen" :aria-expanded="summaryOpen" aria-controls="mobile-order-summary"><span class="font-semibold" x-text="summaryOpen ? 'Hide order summary' : 'Show order summary'"></span><strong><x-storefront.price :amount="$this->totals['total']" :currency="$checkout->cart->currency" /></strong></button>
    <div id="mobile-order-summary" x-show="summaryOpen" x-cloak class="mb-8 lg:hidden">@include('storefront.checkout._summary')</div>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,3fr)_minmax(20rem,2fr)] lg:gap-16">
        <div aria-label="Checkout information">
            <h1 class="sr-only">Checkout</h1>

            <section class="sf-checkout-step" @if($step === 1) data-current-step @endif>
                <div class="flex items-start justify-between gap-4"><div><p class="sf-step-number">Step 1</p><h2 class="sf-step-title">Contact information</h2></div>@if($step > 1)<button wire:click="editStep(1)" class="sf-text-link min-h-11 px-2 text-sm">Edit</button>@endif</div>
                @if ($step === 1)
                    <form wire:submit="saveContact" class="mt-6">
                        <label for="checkout-email" class="sf-label">Email address <span aria-hidden="true">*</span></label>
                        <input id="checkout-email" name="email" type="email" wire:model.blur="email" autocomplete="email" required autofocus class="sf-input mt-1 w-full @error('email') sf-input-error @enderror" @error('email') aria-invalid="true" aria-describedby="checkout-email-error" @enderror>
                        @error('email')<p id="checkout-email-error" class="sf-field-error">{{ $message }}</p>@enderror
                        @guest('customer')<p class="mt-3 text-sm text-slate-500">Already have an account? <a href="{{ url('/account/login') }}" class="sf-text-link">Log in</a></p>@endguest
                        <button class="sf-button sf-button-primary mt-6" wire:loading.attr="disabled" wire:target="saveContact">Continue to shipping</button>
                    </form>
                @elseif ($step > 1)
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $email }}</p>
                @else
                    <p class="mt-3 text-sm text-slate-400">Enter your contact details to continue.</p>
                @endif
            </section>

            <section class="sf-checkout-step" @if($step === 2) data-current-step @endif>
                <div class="flex items-start justify-between gap-4"><div><p class="sf-step-number">Step 2</p><h2 class="sf-step-title">Shipping address</h2></div>@if($step > 2)<button wire:click="editStep(2)" class="sf-text-link min-h-11 px-2 text-sm">Edit</button>@endif</div>
                @if ($step === 2)
                    <form wire:submit="saveAddress" class="mt-6">
                        @if ($this->savedAddresses->isNotEmpty())
                            <div class="mb-6 rounded-xl bg-blue-50 p-4 dark:bg-blue-950/40"><label for="saved-address" class="sf-label">Select a saved address</label><select id="saved-address" class="sf-select mt-1 w-full" wire:change="useSavedAddress($event.target.value)"><option value="">Use a new address</option>@foreach($this->savedAddresses as $address)<option value="{{ $address->id }}">{{ $address->label ?: 'Address' }}{{ $address->is_default ? ' (default)' : '' }}</option>@endforeach</select></div>
                        @endif
                        @include('storefront.checkout._address-fields', ['prefix' => 'shipping'])
                        <label class="mt-6 flex min-h-11 cursor-pointer items-center gap-3 text-sm font-medium"><input type="checkbox" wire:model.live="billingSameAsShipping" class="sf-checkbox"> Billing address same as shipping</label>
                        @if (! $billingSameAsShipping)<fieldset class="mt-7 border-t border-slate-200 pt-7 dark:border-slate-800"><legend class="mb-5 text-lg font-semibold">Billing address</legend>@include('storefront.checkout._address-fields', ['prefix' => 'billing'])</fieldset>@endif
                        <button class="sf-button sf-button-primary mt-7" wire:loading.attr="disabled" wire:target="saveAddress"><span wire:loading.remove wire:target="saveAddress">Continue to shipping methods</span><span wire:loading wire:target="saveAddress">Saving address...</span></button>
                    </form>
                @elseif ($step > 2)
                    <address class="mt-3 text-sm not-italic leading-6 text-slate-600 dark:text-slate-300">{{ $shipping['first_name'] }} {{ $shipping['last_name'] }}<br>{{ $shipping['address1'] }}@if($shipping['address2']), {{ $shipping['address2'] }}@endif<br>{{ $shipping['postal_code'] }} {{ $shipping['city'] }}, {{ $shipping['country'] }}</address>
                @else<p class="mt-3 text-sm text-slate-400">Available after contact information.</p>@endif
            </section>

            <section class="sf-checkout-step" @if($step === 3) data-current-step @endif>
                <div class="flex items-start justify-between gap-4"><div><p class="sf-step-number">Step 3</p><h2 class="sf-step-title">Shipping method</h2></div>@if($step > 3)<button wire:click="editStep(3)" class="sf-text-link min-h-11 px-2 text-sm">Edit</button>@endif</div>
                @if ($step === 3)
                    <form wire:submit="chooseShipping" class="mt-6">
                        <fieldset><legend class="sr-only">Choose a shipping method</legend>
                            @if (! $requiresShipping)
                                <div class="sf-callout sf-callout-info">This order contains only digital products, so no shipping is required.</div>
                            @else
                            @forelse ($shippingRates as $rate)
                                <label class="mb-3 flex min-h-16 cursor-pointer items-center gap-4 rounded-xl border border-slate-300 p-4 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:border-slate-700 dark:has-[:checked]:bg-blue-950/40"><input type="radio" wire:model="shippingRateId" value="{{ $rate['id'] }}" class="sf-radio"><span class="min-w-0 flex-1"><strong class="block text-sm">{{ $rate['name'] }}</strong><span class="mt-1 block text-xs text-slate-500">{{ $rate['description'] }}</span></span><strong class="text-sm">@if($rate['amount'] === 0)Free @else<x-storefront.price :amount="$rate['amount']" :currency="$checkout->cart->currency" />@endif</strong></label>
                            @empty
                                <div class="sf-callout sf-callout-warning" role="alert">No shipping methods are available for your address. Please verify your address or contact us.</div>
                            @endforelse
                            @endif
                        </fieldset>
                        @error('shippingRateId')<p class="sf-field-error" role="alert">{{ $message }}</p>@enderror
                        @if($shippingRates !== [] || ! $requiresShipping)<button class="sf-button sf-button-primary mt-5" wire:loading.attr="disabled" wire:target="chooseShipping">Continue to payment</button>@endif
                    </form>
                @elseif ($step > 3)
                    @php($chosenRate = collect($shippingRates)->firstWhere('id', $shippingRateId))
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ data_get($chosenRate, 'name', 'Selected shipping method') }}</p>
                @else<p class="mt-3 text-sm text-slate-400">Available after your address.</p>@endif
            </section>

            <section class="sf-checkout-step" @if($step === 4) data-current-step @endif>
                <div><p class="sf-step-number">Step 4</p><h2 class="sf-step-title">Payment method</h2></div>
                @if ($step === 4)
                    <form wire:submit="pay" class="mt-6">
                        <fieldset><legend class="sr-only">Select a payment method</legend><div class="grid gap-3 sm:grid-cols-3">
                            @foreach (['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'] as $value => $label)
                                <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border border-slate-300 p-3 text-sm font-semibold has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:border-slate-700 dark:has-[:checked]:bg-blue-950/40"><input type="radio" wire:model.live="paymentMethod" value="{{ $value }}" class="sf-radio">{{ $label }}</label>
                            @endforeach
                        </div></fieldset>

                        @if ($paymentMethod === 'credit_card')
                            <div class="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-5 dark:bg-slate-900 sm:grid-cols-2">
                                <div class="sm:col-span-2"><label for="card-number" class="sf-label">Card number</label><input id="card-number" name="card_number" wire:model="cardNumber" inputmode="numeric" autocomplete="cc-number" placeholder="4242 4242 4242 4242" maxlength="19" class="sf-input mt-1 w-full @error('cardNumber') sf-input-error @enderror" x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').slice(0,16).replace(/(.{4})/g, '$1 ').trim()">@error('cardNumber')<p class="sf-field-error">{{ $message }}</p>@enderror</div>
                                <div class="sm:col-span-2"><label for="cardholder-name" class="sf-label">Cardholder name</label><input id="cardholder-name" name="cardholder_name" wire:model="cardholderName" autocomplete="cc-name" class="sf-input mt-1 w-full @error('cardholderName') sf-input-error @enderror">@error('cardholderName')<p class="sf-field-error">{{ $message }}</p>@enderror</div>
                                <div><label for="card-expiry" class="sf-label">Expiry</label><input id="card-expiry" name="card_expiry" wire:model="cardExpiry" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5" class="sf-input mt-1 w-full @error('cardExpiry') sf-input-error @enderror">@error('cardExpiry')<p class="sf-field-error">{{ $message }}</p>@enderror</div>
                                <div><label for="card-cvc" class="sf-label">CVC</label><input id="card-cvc" name="card_cvc" type="password" wire:model="cardCvc" inputmode="numeric" autocomplete="cc-csc" maxlength="4" class="sf-input mt-1 w-full @error('cardCvc') sf-input-error @enderror">@error('cardCvc')<p class="sf-field-error">{{ $message }}</p>@enderror</div>
                            </div>
                        @elseif ($paymentMethod === 'paypal')
                            <div class="sf-callout sf-callout-info mt-6">Your PayPal payment will be processed securely by the onsite mock payment provider.</div>
                        @else
                            <div class="sf-callout sf-callout-info mt-6">After placing your order, you will receive bank transfer instructions. We will hold your order for 7 days while awaiting payment.</div>
                        @endif

                        @if ($paymentError)<div class="sf-callout sf-callout-error mt-5" role="alert" aria-live="assertive">{{ $paymentError }}</div>@endif
                        <button class="sf-button sf-button-primary mt-6 min-h-14 w-full text-base" wire:loading.attr="disabled" wire:target="pay"><span wire:loading.remove wire:target="pay">{{ $paymentMethod === 'bank_transfer' ? 'Place order' : ($paymentMethod === 'paypal' ? 'Pay with PayPal' : 'Pay now') }} - <x-storefront.price :amount="$this->totals['total']" :currency="$checkout->cart->currency" /></span><span wire:loading wire:target="pay">Processing...</span></button>
                    </form>
                @else<p class="mt-3 text-sm text-slate-400">Available after shipping.</p>@endif
            </section>
        </div>

        <aside class="hidden lg:block"><div class="sticky top-8">@include('storefront.checkout._summary')</div></aside>
    </div>
</div>
