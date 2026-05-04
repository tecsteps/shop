<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Cart', 'url' => route('cart.show')],
        ['label' => 'Checkout'],
    ]" />

    <div class="mt-8 flex flex-col gap-8 lg:grid lg:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Checkout</h1>
                <div class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                    @foreach (['address' => 'Address', 'shipping' => 'Shipping', 'payment' => 'Payment'] as $key => $label)
                        @php($active = $step === $key || ($key === 'payment' && $step === 'reserved'))
                        <div @class([
                            'rounded-md border px-3 py-2',
                            'border-blue-700 bg-blue-50 text-blue-800 dark:border-blue-300 dark:bg-blue-950 dark:text-blue-100' => $active,
                            'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400' => ! $active,
                        ])>
                            {{ $label }}
                        </div>
                    @endforeach
                </div>
            </div>

            @if (! $cart || $lines->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                    <flux:icon name="shopping-bag" class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <h2 class="mt-4 text-lg font-semibold text-zinc-950 dark:text-white">Your cart is empty</h2>
                    <flux:button :href="route('collections.index')" wire:navigate variant="primary" class="mt-6">
                        Browse products
                    </flux:button>
                </div>
            @else
                <form wire:submit="saveAddress" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="lg">Contact and address</flux:heading>
                            <flux:text class="mt-1">Delivery details</flux:text>
                        </div>

                        @if ($checkout?->status !== \App\Enums\CheckoutStatus::Started)
                            <flux:badge color="green">Saved</flux:badge>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <flux:input wire:model="email" type="email" label="Email" />
                            <flux:error name="email" />
                        </div>

                        <flux:input wire:model="shippingAddress.first_name" label="First name" />
                        <flux:input wire:model="shippingAddress.last_name" label="Last name" />

                        <div class="sm:col-span-2">
                            <flux:input wire:model="shippingAddress.address1" label="Address" />
                            <flux:error name="shippingAddress.address1" />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input wire:model="shippingAddress.address2" label="Apartment, suite, etc." />
                        </div>

                        <flux:input wire:model="shippingAddress.city" label="City" />
                        <flux:input wire:model="shippingAddress.postal_code" label="Postal code" />

                        <flux:input wire:model="shippingAddress.province_code" label="Region code" placeholder="DE-BE" />
                        <flux:select wire:model="shippingAddress.country" label="Country">
                            <flux:select.option value="DE">Germany</flux:select.option>
                            <flux:select.option value="AT">Austria</flux:select.option>
                            <flux:select.option value="CH">Switzerland</flux:select.option>
                        </flux:select>
                    </div>

                    <flux:checkbox wire:model="billingSame" label="Billing address is the same" class="mt-5" />

                    <div class="mt-6 flex justify-end">
                        <flux:button type="submit" variant="primary">
                            Continue
                        </flux:button>
                    </div>
                </form>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="lg">Shipping</flux:heading>
                            <flux:text class="mt-1">{{ $requiresShipping ? 'Available rates' : 'Digital delivery' }}</flux:text>
                        </div>

                        @if ($checkout?->status === \App\Enums\CheckoutStatus::ShippingSelected || $checkout?->status === \App\Enums\CheckoutStatus::PaymentSelected)
                            <flux:badge color="green">Selected</flux:badge>
                        @endif
                    </div>

                    @if ($step === 'address')
                        <p class="mt-5 text-sm text-zinc-600 dark:text-zinc-400">Pending address</p>
                    @elseif (! $requiresShipping)
                        <div class="mt-5 flex items-center justify-between gap-4 rounded-md border border-zinc-200 px-4 py-3 dark:border-zinc-800">
                            <span class="font-medium text-zinc-950 dark:text-white">Digital delivery</span>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">0.00</span>
                        </div>
                    @else
                        <div class="mt-5 space-y-3">
                            @forelse ($rates as $rate)
                                @php($selected = (int) $selectedShippingRateId === (int) $rate->getKey())
                                <button
                                    type="button"
                                    wire:click="$set('selectedShippingRateId', {{ $rate->getKey() }})"
                                    @class([
                                        'flex w-full items-center justify-between gap-4 rounded-md border px-4 py-3 text-left transition',
                                        'border-blue-700 bg-blue-50 dark:border-blue-300 dark:bg-blue-950' => $selected,
                                        'border-zinc-200 hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600' => ! $selected,
                                    ])
                                >
                                    <span class="font-medium text-zinc-950 dark:text-white">{{ $rate->name }}</span>
                                    <x-storefront.price :amount="data_get($rateAmounts, $rate->getKey(), 0)" :currency="$cart->currency" />
                                </button>
                            @empty
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">No rates are available for this address.</p>
                            @endforelse
                        </div>

                        <flux:error name="selectedShippingRateId" />
                    @endif

                    @if ($step !== 'address')
                        <div class="mt-6 flex justify-end">
                            <flux:button wire:click="selectShippingMethod" variant="primary" :disabled="$requiresShipping && ! $selectedShippingRateId">
                                Continue
                            </flux:button>
                        </div>
                    @endif
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="lg">Payment</flux:heading>
                            <flux:text class="mt-1">Method</flux:text>
                        </div>

                        @if ($checkout?->status === \App\Enums\CheckoutStatus::PaymentSelected)
                            <flux:badge color="green">Reserved</flux:badge>
                        @endif
                    </div>

                    @if ($step === 'address' || $step === 'shipping')
                        <p class="mt-5 text-sm text-zinc-600 dark:text-zinc-400">Pending shipping</p>
                    @else
                        <div class="mt-5 space-y-5">
                            <div class="flex gap-2">
                                <flux:input wire:model="discountCode" label="Discount code" placeholder="WELCOME10" />
                                <flux:button type="button" wire:click="applyDiscount" variant="ghost" class="mt-6">
                                    Apply
                                </flux:button>
                            </div>
                            <flux:error name="discountCode" />

                            <flux:select wire:model="paymentMethod" label="Payment method">
                                <flux:select.option value="credit_card">Credit card</flux:select.option>
                                <flux:select.option value="paypal">PayPal</flux:select.option>
                                <flux:select.option value="bank_transfer">Bank transfer</flux:select.option>
                            </flux:select>
                            <flux:error name="paymentMethod" />

                            @if ($paymentMethod === 'credit_card')
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <flux:input wire:model="cardNumber" label="Card number" placeholder="4242 4242 4242 4242" />
                                        <flux:error name="cardNumber" />
                                    </div>
                                    <flux:input wire:model="cardName" label="Name on card" />
                                    <flux:input wire:model="cardExpiry" label="Expiry" placeholder="12/30" />
                                    <flux:input wire:model="cardCvc" label="CVC" />
                                </div>
                            @endif

                            @if ($step === 'reserved')
                                <flux:button wire:click="placeOrder" wire:loading.attr="disabled" variant="primary" class="w-full">
                                    Place order
                                </flux:button>
                            @else
                                <flux:button wire:click="selectPaymentMethod" wire:loading.attr="disabled" variant="primary" class="w-full">
                                    Reserve items
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <aside class="h-fit rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Order summary</h2>

            <div class="mt-5 divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($lines as $line)
                    <div class="flex gap-3 py-3 first:pt-0" wire:key="checkout-line-{{ $line->getKey() }}">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-md border border-zinc-200 bg-zinc-100 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-600">
                            <flux:icon name="shopping-bag" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-1 text-sm font-medium text-zinc-950 dark:text-white">{{ $line->variant->product->title }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty {{ $line->quantity }}</p>
                        </div>
                        <x-storefront.price :amount="$line->line_total_amount" :currency="$cart?->currency ?? data_get($totals, 'currency')" class="justify-end text-sm" />
                    </div>
                @endforeach
            </div>

            <div class="mt-5 space-y-3 border-t border-zinc-200 pt-5 text-sm dark:border-zinc-800">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                    <x-storefront.price :amount="data_get($totals, 'subtotal', 0)" :currency="data_get($totals, 'currency', $cart?->currency ?? 'EUR')" />
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Discount</span>
                    <span class="font-semibold text-zinc-950 dark:text-white">-{{ \App\Support\Money::format((int) data_get($totals, 'discount', 0), data_get($totals, 'currency', $cart?->currency ?? 'EUR')) }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Shipping</span>
                    <x-storefront.price :amount="data_get($totals, 'shipping', 0)" :currency="data_get($totals, 'currency', $cart?->currency ?? 'EUR')" />
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Tax</span>
                    <x-storefront.price :amount="data_get($totals, 'tax', 0)" :currency="data_get($totals, 'currency', $cart?->currency ?? 'EUR')" />
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-3 text-base dark:border-zinc-800">
                    <span class="font-semibold text-zinc-950 dark:text-white">Total</span>
                    <x-storefront.price :amount="data_get($totals, 'total', 0)" :currency="data_get($totals, 'currency', $cart?->currency ?? 'EUR')" />
                </div>
            </div>
        </aside>
    </div>
</section>
