<div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
    <div class="flex flex-col gap-6">
        <h1 class="text-3xl font-semibold tracking-normal">Checkout</h1>

        @error('checkout')
            <p class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</p>
        @enderror

        <section class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="text-lg font-semibold tracking-normal">Contact and address</h2>
            <form wire:submit="saveAddress" class="mt-5 grid gap-4 sm:grid-cols-2">
                <input wire:model="shippingAddress.first_name" placeholder="First name" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <input wire:model="shippingAddress.last_name" placeholder="Last name" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <input wire:model="shippingAddress.address1" placeholder="Address" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
                <input wire:model="shippingAddress.address2" placeholder="Apartment, suite, etc." class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
                <input wire:model="shippingAddress.city" placeholder="City" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <input wire:model="shippingAddress.postal_code" placeholder="Postal code" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <input wire:model="shippingAddress.province" placeholder="Region" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <input wire:model="shippingAddress.country_code" placeholder="Country code" maxlength="2" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-900">
                <button type="submit" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950 sm:col-span-2">
                    Save address
                </button>
            </form>
        </section>

        <section class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="text-lg font-semibold tracking-normal">Shipping</h2>
            @if($shippingMethods->isEmpty())
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">Enter a shipping address to see rates.</p>
            @else
                <form wire:submit="selectShipping" class="mt-4 flex flex-col gap-3">
                    @foreach($shippingMethods as $quote)
                        <label wire:key="shipping-rate-{{ $quote->rate->id }}" class="flex cursor-pointer items-center justify-between gap-4 rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                            <span class="flex items-center gap-3">
                                <input wire:model="selectedShippingRateId" type="radio" value="{{ $quote->rate->id }}" class="size-4">
                                <span class="text-sm font-medium">{{ $quote->rate->name }}</span>
                            </span>
                            <span class="text-sm font-semibold">@include('storefront.components.price', ['amount' => $quote->amount, 'currency' => $quote->currency])</span>
                        </label>
                    @endforeach
                    <button type="submit" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                        Select shipping
                    </button>
                </form>
            @endif
        </section>

        <section class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="text-lg font-semibold tracking-normal">Payment</h2>
            @error('payment')
                <p class="mt-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</p>
            @enderror
            <form wire:submit="pay" class="mt-4 flex flex-col gap-3">
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                        <input wire:model.live="paymentMethod" type="radio" value="credit_card" class="size-4">
                        <span class="font-medium">Credit card</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                        <input wire:model.live="paymentMethod" type="radio" value="paypal" class="size-4">
                        <span class="font-medium">PayPal</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                        <input wire:model.live="paymentMethod" type="radio" value="bank_transfer" class="size-4">
                        <span class="font-medium">Bank transfer</span>
                    </label>
                </div>

                @if($paymentMethod === 'credit_card')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input wire:model="cardNumber" inputmode="numeric" autocomplete="cc-number" placeholder="Card number" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
                        <input wire:model="cardHolder" autocomplete="cc-name" placeholder="Cardholder name" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 sm:col-span-2">
                        <input wire:model="cardExpiry" autocomplete="cc-exp" placeholder="MM/YY" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <input wire:model="cardCvc" inputmode="numeric" autocomplete="cc-csc" placeholder="CVC" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    </div>
                @elseif($paymentMethod === 'bank_transfer')
                    <p class="rounded-md border border-zinc-200 p-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">Your order will be held while payment is pending.</p>
                @endif

                <button type="submit" class="rounded-md bg-zinc-950 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                    <span wire:loading.remove wire:target="pay">
                        {{ $paymentMethod === 'bank_transfer' ? 'Place order' : ($paymentMethod === 'paypal' ? 'Pay with PayPal' : 'Pay now') }}
                        -
                        @include('storefront.components.price', ['amount' => $totals['total'] ?? 0, 'currency' => $totals['currency'] ?? $checkout->cart->currency])
                    </span>
                    <span wire:loading wire:target="pay">Processing...</span>
                </button>
            </form>
        </section>
    </div>

    <aside class="h-max rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
        <h2 class="text-lg font-semibold tracking-normal">Order summary</h2>
        <div class="mt-4 flex flex-col gap-3">
            @foreach($checkout->cart->lines as $line)
                <div wire:key="checkout-line-{{ $line->id }}" class="flex justify-between gap-4 text-sm">
                    <span>{{ $line->variant->product->title }} × {{ $line->quantity }}</span>
                    <span>@include('storefront.components.price', ['amount' => $line->line_total_amount, 'currency' => $checkout->cart->currency])</span>
                </div>
            @endforeach
        </div>

        <form wire:submit="applyDiscount" class="mt-5 flex gap-2">
            <input wire:model="discountCode" placeholder="Discount code" class="min-w-0 flex-1 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <button type="submit" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold dark:border-zinc-700">Apply</button>
        </form>
        @if($checkout->discount_code)
            <button type="button" wire:click="removeDiscount" class="mt-2 text-sm font-semibold text-zinc-700 underline dark:text-zinc-300">Remove {{ $checkout->discount_code }}</button>
        @endif
        @error('discountCode')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <dl class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>@include('storefront.components.price', ['amount' => $totals['subtotal'] ?? 0, 'currency' => $totals['currency'] ?? $checkout->cart->currency])</dd></div>
            <div class="flex justify-between"><dt>Discount</dt><dd>-@include('storefront.components.price', ['amount' => $totals['discount'] ?? 0, 'currency' => $totals['currency'] ?? $checkout->cart->currency])</dd></div>
            <div class="flex justify-between"><dt>Shipping</dt><dd>@include('storefront.components.price', ['amount' => $totals['shipping'] ?? 0, 'currency' => $totals['currency'] ?? $checkout->cart->currency])</dd></div>
            <div class="flex justify-between"><dt>Tax</dt><dd>@include('storefront.components.price', ['amount' => $totals['tax'] ?? 0, 'currency' => $totals['currency'] ?? $checkout->cart->currency])</dd></div>
            <div class="flex justify-between pt-3 text-base font-semibold"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $totals['total'] ?? 0, 'currency' => $totals['currency'] ?? $checkout->cart->currency])</dd></div>
        </dl>
    </aside>
</div>
