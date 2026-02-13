<div>
    <h1 class="text-3xl font-bold tracking-tight">Checkout</h1>

    {{-- Step Indicator --}}
    <nav class="mt-6 mb-8">
        <ol class="flex items-center gap-2 text-sm font-medium">
            @foreach ([1 => 'Contact', 2 => 'Address', 3 => 'Delivery', 4 => 'Payment'] as $num => $label)
                <li class="flex items-center gap-2">
                    <button
                        wire:click="goToStep({{ $num }})"
                        @class([
                            'flex size-7 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                            'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $step >= $num,
                            'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' => $step < $num,
                        ])
                        @if($step <= $num) disabled @endif
                    >
                        {{ $num }}
                    </button>
                    <span @class([
                        'text-zinc-900 dark:text-zinc-100' => $step >= $num,
                        'text-zinc-400 dark:text-zinc-500' => $step < $num,
                    ])>
                        {{ $label }}
                    </span>
                    @if ($num < 4)
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="mx-1 size-4 text-zinc-300 dark:text-zinc-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="grid grid-cols-1 gap-10 lg:grid-cols-5">
        {{-- Left Column: Steps --}}
        <div class="lg:col-span-3">

            {{-- Step 1: Contact --}}
            @if ($step === 1)
                <div>
                    <h2 class="text-xl font-semibold">Contact Information</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">We will use this email to send your order confirmation.</p>

                    <form wire:submit="submitContact" class="mt-6 space-y-4">
                        <flux:field>
                            <flux:label>Email address</flux:label>
                            <flux:input type="email" wire:model="email" placeholder="you@example.com" required />
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </flux:field>

                        <div class="pt-2">
                            <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                                Continue to Address
                            </flux:button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Step 2: Address --}}
            @if ($step === 2)
                <div>
                    <h2 class="text-xl font-semibold">Shipping Address</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Where should we deliver your order?</p>

                    <form wire:submit="submitAddress" class="mt-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>First name</flux:label>
                                <flux:input wire:model="firstName" required />
                                @error('firstName')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Last name</flux:label>
                                <flux:input wire:model="lastName" required />
                                @error('lastName')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </flux:field>
                        </div>

                        <flux:field>
                            <flux:label>Address</flux:label>
                            <flux:input wire:model="address1" placeholder="Street and house number" required />
                            @error('address1')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </flux:field>

                        <flux:field>
                            <flux:label>Address line 2 (optional)</flux:label>
                            <flux:input wire:model="address2" placeholder="Apartment, suite, etc." />
                            @error('address2')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </flux:field>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>City</flux:label>
                                <flux:input wire:model="city" required />
                                @error('city')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Postal code</flux:label>
                                <flux:input wire:model="postalCode" required />
                                @error('postalCode')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </flux:field>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label>Country code</flux:label>
                                <flux:input wire:model="countryCode" maxlength="2" placeholder="DE" required />
                                @error('countryCode')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Phone (optional)</flux:label>
                                <flux:input wire:model="phone" type="tel" />
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </flux:field>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <flux:button type="button" wire:click="goToStep(1)">
                                Back
                            </flux:button>
                            <flux:button type="submit" variant="primary">
                                Continue to Delivery
                            </flux:button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Step 3: Shipping / Delivery --}}
            @if ($step === 3)
                <div>
                    <h2 class="text-xl font-semibold">Delivery Method</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Select your preferred shipping option.</p>

                    <form wire:submit="submitShipping" class="mt-6 space-y-4">
                        @if (count($availableShippingRates) === 0)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                No shipping options are available for your address. Please go back and update your address.
                            </p>
                        @else
                            <div class="space-y-3">
                                @foreach ($availableShippingRates as $rate)
                                    <label wire:key="rate-{{ $rate['id'] }}" class="flex cursor-pointer items-center gap-4 rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50 has-[:checked]:border-zinc-900 has-[:checked]:bg-zinc-50 dark:has-[:checked]:border-zinc-400 dark:has-[:checked]:bg-zinc-800/50">
                                        <input
                                            type="radio"
                                            wire:model="selectedShippingRateId"
                                            name="shipping_rate"
                                            value="{{ $rate['id'] }}"
                                            class="size-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600 dark:text-zinc-100 dark:focus:ring-zinc-100"
                                        />
                                        <div class="flex flex-1 items-center justify-between">
                                            <span class="text-sm font-medium">{{ $rate['name'] }}</span>
                                            <span class="text-sm font-semibold">{{ number_format($rate['amount'] / 100, 2) }} EUR</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedShippingRateId')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @endif

                        <div class="flex items-center gap-3 pt-2">
                            <flux:button type="button" wire:click="goToStep(2)">
                                Back
                            </flux:button>
                            @if (count($availableShippingRates) > 0)
                                <flux:button type="submit" variant="primary">
                                    Continue to Payment
                                </flux:button>
                            @endif
                        </div>
                    </form>
                </div>
            @endif

            {{-- Step 4: Payment --}}
            @if ($step === 4)
                <div>
                    <h2 class="text-xl font-semibold">Payment</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Choose your payment method and complete your order.</p>

                    @if ($error)
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                            {{ $error }}
                        </div>
                    @endif

                    <form wire:submit="submitPayment" class="mt-6 space-y-6">
                        {{-- Payment Method Selection --}}
                        <div class="space-y-3">
                            <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50 has-[:checked]:border-zinc-900 has-[:checked]:bg-zinc-50 dark:has-[:checked]:border-zinc-400 dark:has-[:checked]:bg-zinc-800/50">
                                <input type="radio" wire:model.live="paymentMethod" name="payment_method" value="credit_card" class="size-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600 dark:text-zinc-100 dark:focus:ring-zinc-100" />
                                <span class="text-sm font-medium">Credit Card</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50 has-[:checked]:border-zinc-900 has-[:checked]:bg-zinc-50 dark:has-[:checked]:border-zinc-400 dark:has-[:checked]:bg-zinc-800/50">
                                <input type="radio" wire:model.live="paymentMethod" name="payment_method" value="paypal" class="size-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600 dark:text-zinc-100 dark:focus:ring-zinc-100" />
                                <span class="text-sm font-medium">PayPal</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50 has-[:checked]:border-zinc-900 has-[:checked]:bg-zinc-50 dark:has-[:checked]:border-zinc-400 dark:has-[:checked]:bg-zinc-800/50">
                                <input type="radio" wire:model.live="paymentMethod" name="payment_method" value="bank_transfer" class="size-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600 dark:text-zinc-100 dark:focus:ring-zinc-100" />
                                <span class="text-sm font-medium">Bank Transfer</span>
                            </label>
                        </div>

                        {{-- Credit Card Fields --}}
                        @if ($paymentMethod === 'credit_card')
                            <div class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <flux:field>
                                    <flux:label>Card number</flux:label>
                                    <flux:input wire:model="cardNumber" placeholder="4242 4242 4242 4242" required />
                                    @error('cardNumber')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Cardholder name</flux:label>
                                    <flux:input wire:model="cardholderName" placeholder="John Doe" required />
                                    @error('cardholderName')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </flux:field>

                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Expiry</flux:label>
                                        <flux:input wire:model="cardExpiry" placeholder="MM/YY" required />
                                        @error('cardExpiry')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>CVC</flux:label>
                                        <flux:input wire:model="cardCvc" placeholder="123" required />
                                        @error('cardCvc')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </flux:field>
                                </div>
                            </div>
                        @endif

                        @error('paymentMethod')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="flex items-center gap-3 pt-2">
                            <flux:button type="button" wire:click="goToStep(3)">
                                Back
                            </flux:button>
                            <flux:button type="submit" variant="primary">
                                Pay Now - {{ number_format(($totals['total'] ?? 0) / 100, 2) }} EUR
                            </flux:button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        {{-- Right Column: Order Summary --}}
        <div class="lg:col-span-2">
            <div class="sticky top-24 rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800/50">
                <h2 class="text-lg font-semibold">Order Summary</h2>

                <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($cartLines as $line)
                        <div wire:key="line-{{ $line->id }}" class="flex items-start justify-between gap-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $line->variant->product->title ?? 'Product' }}</p>
                                @if ($line->variant->title && $line->variant->title !== 'Default')
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant->title }}</p>
                                @endif
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty: {{ $line->quantity }}</p>
                            </div>
                            <p class="text-sm font-medium">{{ number_format($line->line_total_amount / 100, 2) }} EUR</p>
                        </div>
                    @endforeach
                </div>

                @if (! empty($totals))
                    <div class="mt-4 space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                            <span>{{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }} EUR</span>
                        </div>

                        @if (($totals['discount'] ?? 0) > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">Discount</span>
                                <span class="text-green-600 dark:text-green-400">-{{ number_format($totals['discount'] / 100, 2) }} EUR</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                            <span>{{ number_format(($totals['shipping'] ?? 0) / 100, 2) }} EUR</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                            <span>{{ number_format(($totals['tax_total'] ?? 0) / 100, 2) }} EUR</span>
                        </div>

                        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-700">
                            <span>Total</span>
                            <span>{{ number_format(($totals['total'] ?? 0) / 100, 2) }} EUR</span>
                        </div>
                    </div>
                @else
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Totals will be calculated after selecting a delivery method.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
