@php
    use App\Support\Storefront\PriceFormatter;
    $currency = $checkout->cart->currency;
    $money = fn ($amount) => PriceFormatter::format((int) ($amount ?? 0), $currency);
@endphp

<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8" data-testid="checkout">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('storefront.home')],
        ['label' => __('Cart'), 'url' => route('storefront.cart')],
        ['label' => __('Checkout')],
    ]" />

    <h1 class="mb-8 mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{{ __('Checkout') }}</h1>

    <p class="sr-only" data-testid="checkout-status">{{ __('Step') }}: {{ $checkout->status->value }}</p>

    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_24rem]">
        {{-- Form steps. --}}
        <div class="space-y-10">
            {{-- Step 1: contact + shipping address. --}}
            <section>
                <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">{{ __('1. Contact & shipping address') }}</h2>
                <div class="grid gap-4">
                    <flux:input type="email" wire:model="email" :label="__('Email')" autocomplete="email" required />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input type="text" wire:model="address.first_name" :label="__('First name')" autocomplete="given-name" required />
                        <flux:input type="text" wire:model="address.last_name" :label="__('Last name')" autocomplete="family-name" required />
                    </div>
                    <flux:input type="text" wire:model="address.address1" :label="__('Address')" autocomplete="address-line1" required />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input type="text" wire:model="address.city" :label="__('City')" autocomplete="address-level2" required />
                        <flux:input type="text" wire:model="address.postal_code" :label="__('Postal code')" autocomplete="postal-code" required />
                    </div>
                    <flux:input type="text" wire:model="address.country" :label="__('Country (2-letter ISO)')" maxlength="2" autocomplete="country" required />
                </div>
                @error('address') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                <flux:button type="button" wire:click="saveAddress" variant="primary" class="mt-4">{{ __('Continue to shipping') }}</flux:button>
            </section>

            {{-- Step 2: shipping method. --}}
            @if ($rates->isNotEmpty())
                <section>
                    <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">{{ __('2. Shipping method') }}</h2>
                    <fieldset class="space-y-2">
                        <legend class="sr-only">{{ __('Shipping method') }}</legend>
                        @foreach ($rates as $rate)
                            <label class="flex cursor-pointer items-center justify-between rounded-lg border px-4 py-3 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:border-zinc-700 dark:has-[:checked]:bg-blue-950" wire:key="rate-{{ $rate->id }}">
                                <span class="flex items-center gap-3">
                                    <input type="radio" wire:model="shippingRateId" value="{{ $rate->id }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-zinc-900 dark:text-white">{{ $rate->name }}</span>
                                </span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $money($rate->amount ?? 0) }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    <flux:button type="button" wire:click="selectShipping" variant="primary" class="mt-4">{{ __('Continue to payment') }}</flux:button>
                </section>
            @endif

            {{-- Step 3: payment. --}}
            <section x-data="{ method: @entangle('paymentMethod') }">
                <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">{{ __('3. Payment') }}</h2>
                <fieldset class="space-y-2">
                    <legend class="sr-only">{{ __('Payment method') }}</legend>
                    @foreach (['credit_card' => __('Credit card'), 'paypal' => __('PayPal'), 'bank_transfer' => __('Bank transfer')] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:border-zinc-700 dark:has-[:checked]:bg-blue-950" wire:key="pay-{{ $value }}">
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $value }}" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-900 dark:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </fieldset>

                {{-- Credit card form. --}}
                <div x-show="method === 'credit_card'" class="mt-4">
                    <flux:input type="text" wire:model="cardNumber" :label="__('Card number')" placeholder="4242 4242 4242 4242" inputmode="numeric" />
                </div>
                {{-- PayPal / bank transfer messaging. --}}
                <p x-show="method === 'paypal'" x-cloak class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Your PayPal payment will be processed securely.') }}</p>
                <p x-show="method === 'bank_transfer'" x-cloak class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.') }}</p>

                @error('payment') <p class="mt-2 text-sm text-red-600 dark:text-red-400" data-testid="payment-error">{{ $message }}</p> @enderror

                <button type="button" wire:click="pay" wire:loading.attr="disabled" data-testid="pay-button"
                        class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="pay">
                        <span x-show="method === 'bank_transfer'">{{ __('Place order') }} &mdash; {{ $money($totals['total'] ?? 0) }}</span>
                        <span x-show="method !== 'bank_transfer'">{{ __('Pay now') }} &mdash; {{ $money($totals['total'] ?? 0) }}</span>
                    </span>
                    <span wire:loading wire:target="pay">{{ __('Processing...') }}</span>
                </button>
            </section>
        </div>

        {{-- Order summary sidebar. --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl bg-zinc-50 p-6 dark:bg-zinc-900" data-testid="totals">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Order summary') }}</h2>

                <ul role="list" class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($checkout->cart->lines as $line)
                        <li class="flex items-center justify-between py-3 text-sm" wire:key="summary-line-{{ $line->id }}">
                            <span class="min-w-0 truncate text-zinc-700 dark:text-zinc-300">
                                {{ $line->variant?->product?->title }} <span class="text-zinc-400">&times;{{ $line->quantity }}</span>
                            </span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $money($line->line_total_amount) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 flex gap-2">
                    <flux:input wire:model="discountCode" :placeholder="__('Discount code')" class="flex-1" />
                    <flux:button type="button" wire:click="applyDiscount" variant="filled">{{ __('Apply') }}</flux:button>
                </div>
                @if ($discountError) <p class="mt-2 text-sm text-red-600 dark:text-red-400" data-testid="discount-error">{{ $discountError }}</p> @endif

                <dl class="mt-4 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800" aria-live="polite">
                    <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Subtotal') }}</dt><dd>{{ $money($totals['subtotal'] ?? 0) }}</dd></div>
                    @if (($totals['discount'] ?? 0) > 0)
                        <div class="flex justify-between text-green-600 dark:text-green-400"><dt>{{ __('Discount') }}</dt><dd>-{{ $money($totals['discount']) }}</dd></div>
                    @endif
                    <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Shipping') }}</dt><dd>{{ $money($totals['shipping'] ?? 0) }}</dd></div>
                    <div class="flex justify-between text-zinc-600 dark:text-zinc-300"><dt>{{ __('Tax') }}</dt><dd>{{ $money($totals['tax'] ?? 0) }}</dd></div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white"><dt>{{ __('Total') }}</dt><dd data-testid="total">{{ $money($totals['total'] ?? 0) }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>
</div>
