<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8" data-testid="checkout">
    {{-- Minimal functional multi-step checkout. Storefront teammate (task #6)
         replaces with the polished stepper; service calls + field names stay. --}}
    <h1 class="mb-8 text-2xl font-bold tracking-tight">{{ __('Checkout') }}</h1>

    <p class="mb-6 text-sm text-zinc-500" data-testid="checkout-status">{{ __('Step') }}: {{ $checkout->status->value }}</p>

    {{-- Step 1: contact + shipping address --}}
    <section class="mb-10">
        <h2 class="mb-4 text-lg font-semibold">{{ __('Contact & shipping address') }}</h2>
        <div class="grid gap-3">
            <input type="email" wire:model="email" placeholder="{{ __('Email') }}" class="rounded border border-zinc-300 px-3 py-2">
            <div class="grid grid-cols-2 gap-3">
                <input type="text" wire:model="address.first_name" placeholder="{{ __('First name') }}" class="rounded border border-zinc-300 px-3 py-2">
                <input type="text" wire:model="address.last_name" placeholder="{{ __('Last name') }}" class="rounded border border-zinc-300 px-3 py-2">
            </div>
            <input type="text" wire:model="address.address1" placeholder="{{ __('Address') }}" class="rounded border border-zinc-300 px-3 py-2">
            <div class="grid grid-cols-2 gap-3">
                <input type="text" wire:model="address.city" placeholder="{{ __('City') }}" class="rounded border border-zinc-300 px-3 py-2">
                <input type="text" wire:model="address.postal_code" placeholder="{{ __('Postal code') }}" class="rounded border border-zinc-300 px-3 py-2">
            </div>
            <input type="text" wire:model="address.country" placeholder="{{ __('Country (2-letter ISO)') }}" maxlength="2" class="rounded border border-zinc-300 px-3 py-2">
        </div>
        @error('address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        <button type="button" wire:click="saveAddress" class="mt-4 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white">{{ __('Save address') }}</button>
    </section>

    {{-- Step 2: shipping method --}}
    @if ($rates->isNotEmpty())
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold">{{ __('Shipping method') }}</h2>
            @foreach ($rates as $rate)
                <label class="flex items-center gap-3 border-b border-zinc-100 py-2" wire:key="rate-{{ $rate->id }}">
                    <input type="radio" wire:model="shippingRateId" value="{{ $rate->id }}">
                    <span class="text-sm">{{ $rate->name }}</span>
                </label>
            @endforeach
            <button type="button" wire:click="selectShipping" class="mt-4 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white">{{ __('Use this method') }}</button>
        </section>
    @endif

    {{-- Discount --}}
    <section class="mb-10">
        <h2 class="mb-4 text-lg font-semibold">{{ __('Discount code') }}</h2>
        <div class="flex gap-3">
            <input type="text" wire:model="discountCode" placeholder="{{ __('Code') }}" class="flex-1 rounded border border-zinc-300 px-3 py-2">
            <button type="button" wire:click="applyDiscount" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold">{{ __('Apply') }}</button>
        </div>
        @if ($discountError) <p class="mt-2 text-sm text-red-600" data-testid="discount-error">{{ $discountError }}</p> @endif
    </section>

    {{-- Totals --}}
    <section class="mb-10 rounded-lg bg-zinc-50 p-4 text-sm" data-testid="totals">
        <div class="flex justify-between py-1"><span>{{ __('Subtotal') }}</span><span>{{ \App\Support\Storefront\PriceFormatter::format($totals['subtotal'] ?? 0, $checkout->cart->currency) }}</span></div>
        @if (($totals['discount'] ?? 0) > 0)
            <div class="flex justify-between py-1 text-green-700"><span>{{ __('Discount') }}</span><span>-{{ \App\Support\Storefront\PriceFormatter::format($totals['discount'], $checkout->cart->currency) }}</span></div>
        @endif
        <div class="flex justify-between py-1"><span>{{ __('Shipping') }}</span><span>{{ \App\Support\Storefront\PriceFormatter::format($totals['shipping'] ?? 0, $checkout->cart->currency) }}</span></div>
        <div class="flex justify-between py-1"><span>{{ __('Tax') }}</span><span>{{ \App\Support\Storefront\PriceFormatter::format($totals['tax'] ?? 0, $checkout->cart->currency) }}</span></div>
        <div class="mt-2 flex justify-between border-t border-zinc-200 pt-2 font-semibold"><span>{{ __('Total') }}</span><span data-testid="total">{{ \App\Support\Storefront\PriceFormatter::format($totals['total'] ?? 0, $checkout->cart->currency) }}</span></div>
    </section>

    {{-- Step 3: payment --}}
    <section>
        <h2 class="mb-4 text-lg font-semibold">{{ __('Payment') }}</h2>
        <div class="grid gap-2">
            <label class="flex items-center gap-3"><input type="radio" wire:model="paymentMethod" value="credit_card">{{ __('Credit card') }}</label>
            <label class="flex items-center gap-3"><input type="radio" wire:model="paymentMethod" value="paypal">{{ __('PayPal') }}</label>
            <label class="flex items-center gap-3"><input type="radio" wire:model="paymentMethod" value="bank_transfer">{{ __('Bank transfer') }}</label>
        </div>
        <input type="text" wire:model="cardNumber" placeholder="{{ __('Card number') }}" class="mt-3 w-full rounded border border-zinc-300 px-3 py-2">
        @error('payment') <p class="mt-2 text-sm text-red-600" data-testid="payment-error">{{ $message }}</p> @enderror
        <button type="button" wire:click="pay" class="mt-4 w-full rounded-lg bg-zinc-900 px-4 py-3 font-semibold text-white" data-testid="pay-button">{{ __('Place order') }}</button>
    </section>
</div>
