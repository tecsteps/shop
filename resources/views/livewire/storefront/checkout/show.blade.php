<div class="mx-auto max-w-6xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Cart', 'url' => route('storefront.cart.show')],
        ['label' => 'Checkout'],
    ]" />

    <flux:heading size="xl" class="mb-6">Checkout</flux:heading>

    <div class="grid gap-8 lg:grid-cols-5">
        <section class="lg:col-span-3 space-y-8">
            <div class="rounded border p-6 dark:border-zinc-700" data-testid="checkout-step-contact">
                <flux:heading size="md">1. Contact & shipping</flux:heading>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="email" label="Email" type="email" class="sm:col-span-2" required />
                    <flux:input wire:model="address.first_name" label="First name" required />
                    <flux:input wire:model="address.last_name" label="Last name" required />
                    <flux:input wire:model="address.address1" label="Address" class="sm:col-span-2" required />
                    <flux:input wire:model="address.address2" label="Apt / Suite" class="sm:col-span-2" />
                    <flux:input wire:model="address.city" label="City" required />
                    <flux:input wire:model="address.province_code" label="State / Province" />
                    <flux:input wire:model="address.zip" label="Postal code" required />
                    <flux:input wire:model="address.country_code" label="Country code" required />
                </div>

                <flux:button variant="primary" class="mt-4" wire:click="submitAddress" data-testid="checkout-address-submit">Continue</flux:button>
            </div>

            <div class="rounded border p-6 dark:border-zinc-700" data-testid="checkout-step-shipping">
                <flux:heading size="md">2. Shipping method</flux:heading>
                @if (empty($availableRates))
                    <flux:callout icon="truck" class="mt-3" variant="warning">No shipping methods available yet. Continue past step 1 first.</flux:callout>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($availableRates as $rate)
                            <li wire:key="rate-{{ $rate['id'] }}">
                                <label class="flex cursor-pointer items-center justify-between rounded border p-3 dark:border-zinc-700" data-testid="checkout-shipping-rate">
                                    <span class="flex items-center gap-2">
                                        <input type="radio" name="shipping" value="{{ $rate['id'] }}" wire:click="selectShipping({{ $rate['id'] }})" @checked($shippingMethodId === $rate['id'])>
                                        <span>{{ $rate['name'] }}</span>
                                    </span>
                                    <span class="font-semibold">{{ number_format($rate['amount'] / 100, 2) }} {{ $pricing->currency }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded border p-6 dark:border-zinc-700" data-testid="checkout-step-payment">
                <flux:heading size="md">3. Payment</flux:heading>
                <div class="mt-4 space-y-2">
                    <label class="flex items-center gap-2"><input type="radio" wire:model="paymentMethod" value="credit_card"> Credit Card</label>
                    <label class="flex items-center gap-2"><input type="radio" wire:model="paymentMethod" value="paypal"> PayPal</label>
                    <label class="flex items-center gap-2"><input type="radio" wire:model="paymentMethod" value="bank_transfer"> Bank Transfer</label>
                </div>
                <flux:button variant="primary" class="mt-4 w-full" wire:click="placeOrder" data-testid="checkout-place-order">
                    Place order - {{ number_format(($pricing?->total ?? 0) / 100, 2) }} {{ $pricing?->currency }}
                </flux:button>

                @if (! empty($errorMessages))
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-rose-600">
                        @foreach ($errorMessages as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <aside class="lg:col-span-2">
            <div class="sticky top-8 rounded border p-6 dark:border-zinc-700">
                <flux:heading size="md">Order summary</flux:heading>
                <dl class="mt-4 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt>Subtotal</dt>
                        <dd>{{ number_format($pricing->subtotal / 100, 2) }} {{ $pricing->currency }}</dd>
                    </div>
                    @if ($pricing->discount > 0)
                        <div class="flex justify-between text-emerald-600">
                            <dt>Discount</dt>
                            <dd>-{{ number_format($pricing->discount / 100, 2) }} {{ $pricing->currency }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt>Shipping</dt>
                        <dd>{{ number_format($pricing->shipping / 100, 2) }} {{ $pricing->currency }}</dd>
                    </div>
                    @if ($pricing->taxTotal > 0)
                        <div class="flex justify-between">
                            <dt>Tax</dt>
                            <dd>{{ number_format($pricing->taxTotal / 100, 2) }} {{ $pricing->currency }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between pt-2 text-base font-semibold">
                        <dt>Total</dt>
                        <dd data-testid="checkout-total">{{ number_format($pricing->total / 100, 2) }} {{ $pricing->currency }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>
</div>
