<div>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Checkout</h1>

        @if($error)
            <div class="mt-4 rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ $error }}
            </div>
        @endif

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-5">
            <div class="lg:col-span-3">
                {{-- Step: Contact / Address --}}
                @if($step === 'contact')
                    <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Contact & Shipping Address</h2>
                        <form wire:submit="submitAddress" class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" required>
                                @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">First Name</label>
                                    <input type="text" wire:model="firstName" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" required>
                                    @error('firstName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Last Name</label>
                                    <input type="text" wire:model="lastName" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" required>
                                    @error('lastName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address</label>
                                <input type="text" wire:model="address1" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" required>
                                @error('address1') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Apartment, suite, etc. (optional)</label>
                                <input type="text" wire:model="address2" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">City</label>
                                    <input type="text" wire:model="city" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" required>
                                    @error('city') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Postal Code</label>
                                    <input type="text" wire:model="postalCode" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" required>
                                    @error('postalCode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Country</label>
                                <input type="text" wire:model="country" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" required>
                                @error('country') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                                Continue to Shipping
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Step: Shipping --}}
                @if($step === 'shipping')
                    <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Shipping Method</h2>
                        <div class="mt-4 space-y-3">
                            @forelse($availableRates as $rate)
                                <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                                    <input type="radio" wire:model="selectedShippingRateId" value="{{ $rate->id }}" class="text-zinc-900">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $rate->name }}</p>
                                    </div>
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                        ${{ number_format(($rate->config_json['amount'] ?? 0) / 100, 2) }}
                                    </span>
                                </label>
                            @empty
                                <p class="text-sm text-zinc-500">No shipping methods available for your address.</p>
                            @endforelse
                        </div>
                        <button wire:click="submitShipping" class="mt-6 w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Continue to Payment
                        </button>
                    </div>
                @endif

                {{-- Step: Payment --}}
                @if($step === 'payment')
                    <div x-data="{ cardNumber: '' }" class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Payment Method</h2>
                        <div class="mt-4 space-y-3">
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                                <input type="radio" wire:model.live="paymentMethod" value="credit_card" class="text-zinc-900">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">Credit Card</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                                <input type="radio" wire:model.live="paymentMethod" value="paypal" class="text-zinc-900">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">PayPal</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                                <input type="radio" wire:model.live="paymentMethod" value="bank_transfer" class="text-zinc-900">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">Bank Transfer</span>
                            </label>
                        </div>

                        @if($paymentMethod === 'credit_card')
                            <div class="mt-4 rounded-md border border-zinc-200 p-4 dark:border-zinc-700">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Card Number</label>
                                <input type="text" x-model="cardNumber" placeholder="4242 4242 4242 4242" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            </div>
                        @endif

                        <button x-on:click="$wire.submitPayment(cardNumber)" class="mt-6 w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Complete Order
                        </button>
                    </div>
                @endif
            </div>

            {{-- Order Summary Sidebar --}}
            <div class="lg:col-span-2">
                <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Order Summary</h2>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Subtotal</span>
                            <span>${{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span>
                        </div>
                        @if(($totals['discount'] ?? 0) > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-${{ number_format(($totals['discount'] ?? 0) / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Shipping</span>
                            <span>${{ number_format(($totals['shipping'] ?? 0) / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Tax</span>
                            <span>${{ number_format(($totals['tax_total'] ?? 0) / 100, 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="flex justify-between font-semibold text-zinc-900 dark:text-white">
                            <span>Total</span>
                            <span>${{ number_format(($totals['total'] ?? 0) / 100, 2) }}</span>
                        </div>
                    </div>

                    {{-- Discount Code --}}
                    <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        @if($checkout->discount_code)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Code: {{ $checkout->discount_code }}</span>
                                <button wire:click="removeDiscount" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </div>
                        @else
                            <div class="flex gap-2">
                                <input type="text" wire:model="discountCode" placeholder="Discount code" class="flex-1 rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                <button wire:click="applyDiscount" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                    Apply
                                </button>
                            </div>
                            @if($discountError)
                                <p class="mt-1 text-xs text-red-500">{{ $discountError }}</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
