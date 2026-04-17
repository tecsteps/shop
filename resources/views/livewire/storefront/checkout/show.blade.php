<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Checkout</h1>

    @if($checkout)
        <div class="mt-8 space-y-8">
            {{-- Step 1: Contact & Address --}}
            @if($checkout->status->value === 'started')
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Contact & Shipping Address</h2>
                    <form wire:submit="submitAddress" class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                            <input type="email" wire:model="email" id="email" required
                                   class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-zinc-500 focus:ring-zinc-500 sm:text-sm">
                            @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="firstName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">First Name</label>
                                <input type="text" wire:model="firstName" id="firstName" required
                                       class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-zinc-500 focus:ring-zinc-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="lastName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Last Name</label>
                                <input type="text" wire:model="lastName" id="lastName" required
                                       class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-zinc-500 focus:ring-zinc-500 sm:text-sm">
                            </div>
                        </div>
                        <div>
                            <label for="address1" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address</label>
                            <input type="text" wire:model="address1" id="address1" required
                                   class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-zinc-500 focus:ring-zinc-500 sm:text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">City</label>
                                <input type="text" wire:model="city" id="city" required
                                       class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-zinc-500 focus:ring-zinc-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="postalCode" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Postal Code</label>
                                <input type="text" wire:model="postalCode" id="postalCode" required
                                       class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-zinc-500 focus:ring-zinc-500 sm:text-sm">
                            </div>
                        </div>
                        <div>
                            <label for="country" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Country</label>
                            <input type="text" wire:model="country" id="country" required maxlength="2"
                                   class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-zinc-500 focus:ring-zinc-500 sm:text-sm">
                        </div>
                        <button type="submit"
                                class="w-full rounded-md bg-zinc-900 dark:bg-white px-4 py-3 text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                            Continue to Shipping
                        </button>
                    </form>
                </div>
            @endif

            {{-- Step 2: Shipping --}}
            @if($checkout->status->value === 'addressed')
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Shipping Method</h2>
                    @if($availableRates->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No shipping required or no rates available.</p>
                        <button wire:click="selectShipping"
                                class="mt-4 w-full rounded-md bg-zinc-900 dark:bg-white px-4 py-3 text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                            Continue to Payment
                        </button>
                    @else
                        <div class="space-y-2">
                            @foreach($availableRates as $rate)
                                <label class="flex items-center justify-between rounded-md border border-zinc-200 dark:border-zinc-700 p-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    <div class="flex items-center space-x-3">
                                        <input type="radio" wire:model="selectedShippingRateId" value="{{ $rate->id }}"
                                               class="text-zinc-900 focus:ring-zinc-500">
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $rate->name }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <button wire:click="selectShipping"
                                class="mt-4 w-full rounded-md bg-zinc-900 dark:bg-white px-4 py-3 text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                            Continue to Payment
                        </button>
                    @endif
                </div>
            @endif

            {{-- Step 3: Payment --}}
            @if($checkout->status->value === 'shipping_selected')
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Payment</h2>
                    <div class="space-y-2 mb-4">
                        <label class="flex items-center space-x-3">
                            <input type="radio" wire:model="paymentMethod" value="credit_card"
                                   class="text-zinc-900 focus:ring-zinc-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Credit Card</span>
                        </label>
                        <label class="flex items-center space-x-3">
                            <input type="radio" wire:model="paymentMethod" value="paypal"
                                   class="text-zinc-900 focus:ring-zinc-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">PayPal</span>
                        </label>
                        <label class="flex items-center space-x-3">
                            <input type="radio" wire:model="paymentMethod" value="bank_transfer"
                                   class="text-zinc-900 focus:ring-zinc-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Bank Transfer</span>
                        </label>
                    </div>

                    @if($checkout->totals_json)
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Subtotal</span>
                                <span>${{ number_format(($checkout->totals_json['subtotal'] ?? 0) / 100, 2) }}</span>
                            </div>
                            @if(($checkout->totals_json['discount'] ?? 0) > 0)
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Discount</span>
                                    <span>-${{ number_format($checkout->totals_json['discount'] / 100, 2) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Shipping</span>
                                <span>${{ number_format(($checkout->totals_json['shipping'] ?? 0) / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Tax</span>
                                <span>${{ number_format(($checkout->totals_json['tax_total'] ?? 0) / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-semibold text-zinc-900 dark:text-white border-t border-zinc-200 dark:border-zinc-700 pt-2">
                                <span>Total</span>
                                <span>${{ number_format(($checkout->totals_json['total'] ?? 0) / 100, 2) }}</span>
                            </div>
                        </div>
                    @endif

                    <button wire:click="submitPayment"
                            class="mt-4 w-full rounded-md bg-zinc-900 dark:bg-white px-4 py-3 text-sm font-medium text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                        Complete Order
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
