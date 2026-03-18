<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Checkout</h1>

    {{-- Stepper --}}
    <div class="mt-6 flex items-center gap-4 text-sm">
        <span class="{{ $step >= 1 ? 'font-semibold text-zinc-900 dark:text-white' : 'text-zinc-400' }}">1. Contact & Address</span>
        <span class="text-zinc-300">></span>
        <span class="{{ $step >= 2 ? 'font-semibold text-zinc-900 dark:text-white' : 'text-zinc-400' }}">2. Shipping</span>
        <span class="text-zinc-300">></span>
        <span class="{{ $step >= 3 ? 'font-semibold text-zinc-900 dark:text-white' : 'text-zinc-400' }}">3. Payment</span>
    </div>

    @if($errorMessage)
        <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Step 1: Contact & Address --}}
    @if($step === 1)
        <form wire:submit="submitAddress" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                <input wire:model="email" type="email" id="email" required
                       class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="firstName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">First Name</label>
                    <input wire:model="firstName" type="text" id="firstName" required
                           class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @error('firstName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="lastName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Last Name</label>
                    <input wire:model="lastName" type="text" id="lastName" required
                           class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @error('lastName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="address1" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address</label>
                <input wire:model="address1" type="text" id="address1" required
                       class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                @error('address1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="city" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">City</label>
                    <input wire:model="city" type="text" id="city" required
                           class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="postalCode" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Postal Code</label>
                    <input wire:model="postalCode" type="text" id="postalCode" required
                           class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @error('postalCode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="country" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Country</label>
                <input wire:model="country" type="text" id="country" required maxlength="2"
                       class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                Continue to Shipping
            </button>
        </form>
    @endif

    {{-- Step 2: Shipping --}}
    @if($step === 2)
        <form wire:submit="submitShipping" class="mt-8 space-y-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Select Shipping Method</h2>

            @if($availableRates->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">No shipping methods available for your address.</p>
            @else
                <div class="space-y-3">
                    @foreach($availableRates as $rate)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 p-4 dark:border-zinc-600">
                            <input wire:model="selectedRateId" type="radio" name="shipping_rate" value="{{ $rate->id }}"
                                   class="text-zinc-900">
                            <span class="flex-1 text-sm text-zinc-900 dark:text-white">{{ $rate->name }}</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ number_format(($rate->config_json['amount'] ?? 0) / 100, 2) }} EUR
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            @error('selectedRateId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <button type="submit"
                    class="w-full rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                Continue to Payment
            </button>
        </form>
    @endif

    {{-- Step 3: Payment --}}
    @if($step === 3)
        <form wire:submit="submitPayment" class="mt-8 space-y-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Payment Method</h2>

            <div class="space-y-3">
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 p-4 dark:border-zinc-600">
                    <input wire:model="paymentMethod" type="radio" name="payment_method" value="credit_card" class="text-zinc-900">
                    <span class="text-sm text-zinc-900 dark:text-white">Credit Card</span>
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 p-4 dark:border-zinc-600">
                    <input wire:model="paymentMethod" type="radio" name="payment_method" value="paypal" class="text-zinc-900">
                    <span class="text-sm text-zinc-900 dark:text-white">PayPal</span>
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 p-4 dark:border-zinc-600">
                    <input wire:model="paymentMethod" type="radio" name="payment_method" value="bank_transfer" class="text-zinc-900">
                    <span class="text-sm text-zinc-900 dark:text-white">Bank Transfer</span>
                </label>
            </div>

            {{-- Discount code --}}
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Discount Code</h3>
                @if($appliedDiscountCode)
                    <div class="mt-2 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                        <div>
                            <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ $appliedDiscountCode }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400">{{ $discountDescription }}</p>
                        </div>
                        <button wire:click="removeDiscount" type="button" class="text-sm text-green-700 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200">Remove</button>
                    </div>
                @else
                    <div class="mt-2 flex gap-2">
                        <input wire:model="discountCode" type="text" placeholder="Enter code"
                               class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <button wire:click="applyDiscount" type="button"
                                class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Apply
                        </button>
                    </div>
                    @if($discountError)
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $discountError }}</p>
                    @endif
                @endif
            </div>

            @if($checkout && $checkout->totals_json)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Order Summary</h3>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Subtotal</span>
                            <span>{{ number_format(($checkout->totals_json['subtotal'] ?? 0) / 100, 2) }} {{ $checkout->totals_json['currency'] ?? 'EUR' }}</span>
                        </div>
                        @if(($checkout->totals_json['discount'] ?? 0) > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-{{ number_format($checkout->totals_json['discount'] / 100, 2) }} {{ $checkout->totals_json['currency'] ?? 'EUR' }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Shipping</span>
                            <span>{{ number_format(($checkout->totals_json['shipping'] ?? 0) / 100, 2) }} {{ $checkout->totals_json['currency'] ?? 'EUR' }}</span>
                        </div>
                        @if(($checkout->totals_json['tax_total'] ?? 0) > 0)
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Tax</span>
                                <span>{{ number_format($checkout->totals_json['tax_total'] / 100, 2) }} {{ $checkout->totals_json['currency'] ?? 'EUR' }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">
                            <span>Total</span>
                            <span>{{ number_format(($checkout->totals_json['total'] ?? 0) / 100, 2) }} {{ $checkout->totals_json['currency'] ?? 'EUR' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <button type="submit"
                    class="w-full rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                Complete Order
            </button>
        </form>
    @endif
</div>
