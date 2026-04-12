<div class="space-y-10">
    <header class="space-y-2">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">Checkout</h1>
    </header>

    <ol class="flex items-center gap-3 text-sm font-medium">
        @foreach ([1 => 'Address', 2 => 'Shipping', 3 => 'Payment'] as $stepNumber => $label)
            <li class="flex items-center gap-3">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border {{ $step >= $stepNumber ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : 'border-zinc-300 text-zinc-500 dark:border-zinc-700 dark:text-zinc-500' }}">
                    {{ $stepNumber }}
                </span>
                <span class="{{ $step >= $stepNumber ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 dark:text-zinc-500' }}">{{ $label }}</span>
                @if ($stepNumber < 3)
                    <span class="h-px w-8 bg-zinc-300 dark:bg-zinc-700"></span>
                @endif
            </li>
        @endforeach
    </ol>

    <div class="grid gap-10 lg:grid-cols-3">
        <section class="lg:col-span-2 space-y-8">
            @if ($step === 1)
                <form wire:submit.prevent="continueToShipping" class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900" data-testid="checkout-address-form">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Contact and shipping address</h2>

                    <div class="space-y-4">
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Email</span>
                            <input type="email" wire:model="email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('email') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">First name</span>
                                <input type="text" wire:model="shippingAddress.first_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                @error('shippingAddress.first_name') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Last name</span>
                                <input type="text" wire:model="shippingAddress.last_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                @error('shippingAddress.last_name') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Address</span>
                            <input type="text" wire:model="shippingAddress.line1" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('shippingAddress.line1') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </label>

                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Apartment, suite (optional)</span>
                            <input type="text" wire:model="shippingAddress.line2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                        </label>

                        <div class="grid grid-cols-3 gap-3">
                            <label class="col-span-2 block text-sm">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">City</span>
                                <input type="text" wire:model="shippingAddress.city" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                @error('shippingAddress.city') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Postal code</span>
                                <input type="text" wire:model="shippingAddress.postal_code" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                @error('shippingAddress.postal_code') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Country</span>
                            <select wire:model="shippingAddress.country" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                <option value="DE">Germany</option>
                                <option value="AT">Austria</option>
                                <option value="CH">Switzerland</option>
                                <option value="FR">France</option>
                                <option value="NL">Netherlands</option>
                                <option value="US">United States</option>
                                <option value="GB">United Kingdom</option>
                            </select>
                        </label>

                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="billingSameAsShipping" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
                            <span>Billing address is the same as shipping</span>
                        </label>
                    </div>

                    <button type="submit" class="inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        Continue to shipping
                    </button>
                </form>
            @elseif ($step === 2)
                <form wire:submit.prevent="continueToPayment" class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900" data-testid="checkout-shipping-form">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Shipping method</h2>

                    @if ($shippingRates->isEmpty())
                        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            No shipping options available for the selected country.
                        </p>
                    @else
                        <div class="space-y-2">
                            @foreach ($shippingRates as $rate)
                                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-zinc-200 p-4 transition hover:border-zinc-900 dark:border-zinc-800 dark:hover:border-zinc-400 {{ (int) $shippingMethodId === (int) $rate->id ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-200 dark:bg-zinc-950' : '' }}">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" wire:model="shippingMethodId" value="{{ $rate->id }}" class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $rate->name }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        <x-storefront.price :amount="$rate->config_json['amount'] ?? 0" :currency="$cart?->currency ?? 'USD'" />
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('shippingMethodId') <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    @endif

                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="backToAddress" class="rounded-full border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Back
                        </button>
                        <button type="submit" class="inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Continue to payment
                        </button>
                    </div>
                </form>
            @else
                <form wire:submit.prevent="placeOrder" class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900" data-testid="checkout-payment-form">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Payment</h2>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <input type="radio" wire:model.live="paymentMethod" value="credit_card" class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Credit card</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <input type="radio" wire:model.live="paymentMethod" value="paypal" class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">PayPal</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <input type="radio" wire:model.live="paymentMethod" value="bank_transfer" class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Bank transfer</span>
                        </label>
                    </div>

                    @if ($paymentMethod === 'credit_card')
                        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-4 py-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400">
                            Magic test card: 4242 4242 4242 4242, expiry 12/30, CVC 123.
                        </div>

                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Card number</span>
                            <input type="text" wire:model="cardNumber" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Expiry (MM/YY)</span>
                                <input type="text" wire:model="cardExpiry" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">CVC</span>
                                <input type="text" wire:model="cardCvc" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            </label>
                        </div>
                    @endif

                    @error('payment')
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="backToShipping" class="rounded-full border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Back
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            <span wire:loading.remove wire:target="placeOrder">Place order</span>
                            <span wire:loading wire:target="placeOrder">Processing...</span>
                        </button>
                    </div>
                </form>
            @endif
        </section>

        <aside class="h-fit space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Order summary</h2>

            @if ($cart)
                <ul class="space-y-3">
                    @foreach ($cart->lines as $line)
                        <li class="flex items-center gap-3">
                            <div class="h-12 w-12 flex-shrink-0 rounded-lg bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $line->variant?->product?->title ?? 'Product' }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty {{ $line->quantity }}</p>
                            </div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                <x-storefront.price :amount="$line->line_total_amount" :currency="$cart->currency" />
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
                <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Subtotal</span>
                    <span class="text-zinc-900 dark:text-zinc-100"><x-storefront.price :amount="$totals['subtotal']" :currency="$cart?->currency ?? 'USD'" /></span>
                </div>
                <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Shipping</span>
                    <span class="text-zinc-900 dark:text-zinc-100"><x-storefront.price :amount="$totals['shipping']" :currency="$cart?->currency ?? 'USD'" /></span>
                </div>
                <div class="flex items-center justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-zinc-100">
                    <span>Total</span>
                    <span><x-storefront.price :amount="$totals['total']" :currency="$cart?->currency ?? 'USD'" /></span>
                </div>
            </div>
        </aside>
    </div>
</div>
