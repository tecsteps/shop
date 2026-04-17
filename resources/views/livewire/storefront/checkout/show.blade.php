<div class="flex flex-col gap-8">
    <h1 class="text-3xl font-semibold tracking-tight">Checkout</h1>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            <section class="rounded-lg border border-neutral-200 p-6 dark:border-neutral-800">
                <h2 class="text-xl font-semibold">Contact and shipping address</h2>
                <form wire:submit.prevent="saveAddress" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="checkout-email" class="mb-1 block text-sm font-medium">Email</label>
                        <input id="checkout-email" name="email" type="email" wire:model="email" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
                        @error('email') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="checkout-first-name" class="mb-1 block text-sm font-medium">First name</label>
                        <input id="checkout-first-name" name="first_name" type="text" wire:model="first_name" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
                        @error('first_name') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="checkout-last-name" class="mb-1 block text-sm font-medium">Last name</label>
                        <input id="checkout-last-name" name="last_name" type="text" wire:model="last_name" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
                        @error('last_name') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="checkout-address1" class="mb-1 block text-sm font-medium">Address</label>
                        <input id="checkout-address1" name="address1" type="text" wire:model="address1" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
                        @error('address1') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="checkout-city" class="mb-1 block text-sm font-medium">City</label>
                        <input id="checkout-city" name="city" type="text" wire:model="city" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
                        @error('city') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="checkout-province-code" class="mb-1 block text-sm font-medium">State/province code</label>
                        <input id="checkout-province-code" name="province_code" type="text" wire:model="province_code" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
                    </div>
                    <div>
                        <label for="checkout-country-code" class="mb-1 block text-sm font-medium">Country (2-letter)</label>
                        <input id="checkout-country-code" name="country_code" type="text" wire:model="country_code" maxlength="2" class="w-full rounded border border-neutral-300 px-3 py-2 uppercase dark:border-neutral-700 dark:bg-neutral-900" />
                        @error('country_code') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="checkout-postal-code" class="mb-1 block text-sm font-medium">Postal code</label>
                        <input id="checkout-postal-code" name="postal_code" type="text" wire:model="postal_code" class="w-full rounded border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900" />
                        @error('postal_code') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-full bg-neutral-900 px-6 py-2 text-sm font-semibold text-white hover:bg-neutral-700">Save address</button>
                    </div>
                </form>
            </section>

            @if ($rates->isNotEmpty())
                <section class="rounded-lg border border-neutral-200 p-6 dark:border-neutral-800">
                    <h2 class="text-xl font-semibold">Shipping</h2>
                    <div class="mt-4 flex flex-col gap-2">
                        @foreach ($rates as $rate)
                            <label wire:key="rate-{{ $rate->id }}" class="flex items-center justify-between rounded border border-neutral-200 p-3 dark:border-neutral-700">
                                <span class="flex items-center gap-3">
                                    <input type="radio" wire:click="selectShipping({{ $rate->id }})" @checked($shipping_rate_id === (int) $rate->id) />
                                    <span>{{ $rate->name }}</span>
                                </span>
                                <span class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ number_format((int) ($rate->config_json['amount'] ?? 0) / 100, 2) }} {{ $cart->currency }}
                                </span>
                            </label>
                        @endforeach
                        @error('shipping_rate_id') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                </section>
            @endif

            <section class="rounded-lg border border-neutral-200 p-6 dark:border-neutral-800">
                <h2 class="text-xl font-semibold">Payment</h2>
                <form wire:submit.prevent="place" class="mt-4 flex flex-col gap-4">
                    @foreach ($paymentMethods as $method)
                        <label class="flex items-center gap-3 rounded border border-neutral-200 p-3 dark:border-neutral-700">
                            <input type="radio" wire:model.live="payment_method" value="{{ $method->value }}" />
                            <span class="capitalize">{{ str_replace('_', ' ', $method->value) }}</span>
                        </label>
                    @endforeach
                    @if ($payment_method === 'credit_card')
                        <div>
                            <label for="checkout-card-number" class="mb-1 block text-sm font-medium">Card number</label>
                            <input id="checkout-card-number" name="card_number" type="text" wire:model="card_number" maxlength="19" placeholder="4242 4242 4242 4242" class="w-full rounded border border-neutral-300 px-3 py-2 tracking-widest dark:border-neutral-700 dark:bg-neutral-900" />
                        </div>
                    @endif
                    @error('payment_method') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                    @if ($payment_error !== '')
                        <div class="text-xs text-red-600">{{ $payment_error }}</div>
                    @endif
                    <button type="submit" class="rounded-full bg-neutral-900 px-6 py-2 text-sm font-semibold text-white hover:bg-neutral-700">Place order</button>
                </form>
            </section>
        </div>

        <aside class="flex flex-col gap-4 rounded-lg border border-neutral-200 p-6 dark:border-neutral-800">
            <h2 class="text-lg font-semibold">Order summary</h2>
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($lines as $line)
                    <li wire:key="summary-{{ $line->id }}" class="flex justify-between">
                        <span>{{ $line->variant?->product?->title }} &times; {{ $line->quantity }}</span>
                        <span>{{ number_format($line->line_subtotal_amount / 100, 2) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-neutral-200 pt-2 text-sm dark:border-neutral-800">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span></div>
                <div class="flex justify-between"><span>Discount</span><span>-{{ number_format(($totals['discount'] ?? 0) / 100, 2) }}</span></div>
                <div class="flex justify-between"><span>Shipping</span><span>{{ number_format(($totals['shipping'] ?? 0) / 100, 2) }}</span></div>
                <div class="flex justify-between"><span>Tax</span><span>{{ number_format(($totals['tax_total'] ?? 0) / 100, 2) }}</span></div>
                <div class="mt-2 flex justify-between border-t border-neutral-200 pt-2 text-base font-semibold dark:border-neutral-800"><span>Total</span><span>{{ number_format(($totals['total'] ?? 0) / 100, 2) }} {{ $cart->currency }}</span></div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-xs font-medium uppercase text-neutral-500">Discount code</label>
                <div class="flex gap-2">
                    <input type="text" wire:model="discount_code" class="flex-1 rounded border border-neutral-300 px-2 py-1 text-sm dark:border-neutral-700 dark:bg-neutral-900" />
                    <button wire:click="applyDiscount" type="button" class="rounded-full bg-neutral-700 px-3 py-1 text-xs font-medium text-white hover:bg-neutral-900">Apply</button>
                </div>
                @if ($discount_error !== '')
                    <div class="text-xs text-red-600">{{ $discount_error }}</div>
                @endif
                @if (! empty($checkout->discount_code))
                    <button wire:click="removeDiscount" type="button" class="text-xs text-neutral-500 hover:text-red-600">Remove discount</button>
                @endif
            </div>
        </aside>
    </div>
</div>
