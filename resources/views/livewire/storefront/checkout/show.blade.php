<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Checkout</h1>

    {{-- Steps indicator --}}
    <div class="mt-6 flex items-center gap-4 text-sm">
        <span @class(['font-semibold text-zinc-900 dark:text-white' => $step >= 1, 'text-gray-400' => $step < 1])>1. Contact & Address</span>
        <span class="text-gray-300">/</span>
        <span @class(['font-semibold text-zinc-900 dark:text-white' => $step >= 2, 'text-gray-400' => $step < 2])>2. Shipping</span>
        <span class="text-gray-300">/</span>
        <span @class(['font-semibold text-zinc-900 dark:text-white' => $step >= 3, 'text-gray-400' => $step < 3])>3. Payment</span>
    </div>

    @if ($errorMessage)
        <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2" wire:key="checkout-step-{{ $step }}">
            {{-- Step 1: Contact & Address --}}
            @if ($step === 1)
                <form wire:submit="submitAddress" class="space-y-4">
                    <div>
                        <flux:input label="Email" type="email" wire:model="email" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="First name" wire:model="firstName" required />
                        <flux:input label="Last name" wire:model="lastName" required />
                    </div>
                    <flux:input label="Address" wire:model="address1" required />
                    <flux:input label="Apartment, suite, etc." wire:model="address2" />
                    <div class="grid grid-cols-3 gap-4">
                        <flux:input label="City" wire:model="city" required />
                        <flux:input label="State / Province" wire:model="province" />
                        <flux:input label="ZIP code" wire:model="zip" required />
                    </div>
                    <flux:select label="Country" wire:model="country" required>
                        <flux:select.option value="DE">Germany</flux:select.option>
                        <flux:select.option value="AT">Austria</flux:select.option>
                        <flux:select.option value="CH">Switzerland</flux:select.option>
                        <flux:select.option value="NL">Netherlands</flux:select.option>
                        <flux:select.option value="FR">France</flux:select.option>
                        <flux:select.option value="GB">United Kingdom</flux:select.option>
                        <flux:select.option value="US">United States</flux:select.option>
                    </flux:select>
                    <flux:input label="Phone" wire:model="phone" />
                    <flux:button type="submit" variant="primary" class="w-full">Continue to shipping</flux:button>
                </form>
            @endif

            {{-- Step 2: Shipping --}}
            @if ($step === 2)
                <form wire:submit="submitShipping" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Select shipping method</h2>
                    @forelse ($shippingRates as $rate)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <input type="radio" wire:model="selectedShippingRateId" value="{{ $rate->id }}" class="text-zinc-600" />
                            <div class="flex-1">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $rate->name }}</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">${{ number_format($rate->amount / 100, 2) }}</span>
                        </label>
                    @empty
                        <p class="text-gray-500">No shipping methods available for your address.</p>
                    @endforelse
                    <flux:button type="submit" variant="primary" class="w-full">Continue to payment</flux:button>
                </form>
            @endif

            {{-- Step 3: Payment --}}
            @if ($step === 3)
                <form wire:submit="submitPayment" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payment</h2>
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 dark:border-gray-700">
                            <input type="radio" wire:model="paymentMethod" value="credit_card" class="text-zinc-600" />
                            <span class="font-medium text-gray-900 dark:text-white">Credit Card</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 dark:border-gray-700">
                            <input type="radio" wire:model="paymentMethod" value="paypal" class="text-zinc-600" />
                            <span class="font-medium text-gray-900 dark:text-white">PayPal</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 dark:border-gray-700">
                            <input type="radio" wire:model="paymentMethod" value="bank_transfer" class="text-zinc-600" />
                            <span class="font-medium text-gray-900 dark:text-white">Bank Transfer</span>
                        </label>
                    </div>
                    @if ($paymentMethod === 'credit_card')
                        <flux:input label="Card number" wire:model="cardNumber" placeholder="4242 4242 4242 4242" />
                    @endif
                    <flux:button type="submit" variant="primary" class="w-full">Place order</flux:button>
                </form>
            @endif
        </div>

        {{-- Order Summary Sidebar --}}
        <div class="rounded-lg border bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order summary</h2>
            @if ($checkout && $checkout->cart)
                <div class="mt-4 space-y-3">
                    @foreach ($checkout->cart->lines as $line)
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <span class="text-gray-900 dark:text-white">{{ $line->variant->product->title ?? 'Product' }}</span>
                                <span class="text-gray-500"> x{{ $line->quantity }}</span>
                            </div>
                            <span class="text-gray-900 dark:text-white">${{ number_format($line->total / 100, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 border-t pt-4 dark:border-gray-700">
                    <div class="flex justify-between text-sm font-medium text-gray-900 dark:text-white">
                        <span>Subtotal</span>
                        <span>${{ number_format($checkout->cart->lines->sum('total') / 100, 2) }}</span>
                    </div>
                    @if ($checkout->discount_amount > 0)
                        <div class="mt-1 flex justify-between text-sm text-green-600 dark:text-green-400">
                            <span>Discount ({{ $checkout->discount_code }})</span>
                            <span>-${{ number_format($checkout->discount_amount / 100, 2) }}</span>
                        </div>
                    @endif
                    @if ($checkout->shipping_amount > 0)
                        <div class="mt-1 flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Shipping</span>
                            <span>${{ number_format($checkout->shipping_amount / 100, 2) }}</span>
                        </div>
                    @endif
                    @php $checkoutTotal = $checkout->cart->lines->sum('total') - ($checkout->discount_amount ?? 0) + ($checkout->shipping_amount ?? 0); @endphp
                    <div class="mt-2 flex justify-between text-sm font-bold text-gray-900 dark:text-white">
                        <span>Total</span>
                        <span>${{ number_format(max(0, $checkoutTotal) / 100, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
