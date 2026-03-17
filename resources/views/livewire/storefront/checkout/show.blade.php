<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Checkout</h1>

    {{-- Stepper --}}
    <div class="mt-6 flex items-center gap-2 text-sm">
        @foreach(['contact' => 'Contact', 'shipping' => 'Shipping', 'payment' => 'Payment', 'review' => 'Review'] as $step => $label)
            <span @class([
                'font-semibold text-gray-900 dark:text-white' => $currentStep === $step,
                'text-gray-400 dark:text-gray-500' => $currentStep !== $step,
            ])>{{ $label }}</span>
            @if(!$loop->last)
                <span class="text-gray-300 dark:text-gray-600">/</span>
            @endif
        @endforeach
    </div>

    @error('checkout')
        <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">{{ $message }}</div>
    @enderror

    {{-- Contact & Address Step --}}
    @if($currentStep === 'contact')
        <form wire:submit="setAddress" class="mt-6 space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input wire:model="email" type="email" id="email" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <h3 class="pt-4 text-lg font-medium text-gray-900 dark:text-white">Shipping Address</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                    <input wire:model="firstName" type="text" id="firstName" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    @error('firstName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                    <input wire:model="lastName" type="text" id="lastName" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    @error('lastName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="address1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                <input wire:model="address1" type="text" id="address1" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                @error('address1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="address2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apartment, suite, etc. (optional)</label>
                <input wire:model="address2" type="text" id="address2"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
                    <input wire:model="city" type="text" id="city" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="postalCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postal Code</label>
                    <input wire:model="postalCode" type="text" id="postalCode" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    @error('postalCode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country</label>
                    <input wire:model="country" type="text" id="country" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                    @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone (optional)</label>
                    <input wire:model="phone" type="text" id="phone"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                </div>
            </div>

            <button type="submit"
                    class="w-full rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                Continue to Shipping
            </button>
        </form>
    @endif

    {{-- Shipping Step --}}
    @if($currentStep === 'shipping')
        <div class="mt-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Shipping Method</h3>

            @if(count($availableShippingMethods) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">No shipping methods available for this address.</p>
            @else
                <div class="space-y-3">
                    @foreach($availableShippingMethods as $method)
                        <label wire:key="shipping-{{ $method['id'] }}"
                               class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 p-4 hover:border-gray-400 dark:border-gray-700 dark:hover:border-gray-500">
                            <div class="flex items-center gap-3">
                                <input type="radio" wire:model="selectedShippingMethodId" value="{{ $method['id'] }}"
                                       class="text-gray-900 focus:ring-gray-500 dark:text-white">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $method['name'] }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $method['amount'] > 0 ? number_format($method['amount'] / 100, 2) : 'Free' }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <button wire:click="setShippingMethod"
                        @if(!$selectedShippingMethodId) disabled @endif
                        class="w-full rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                    Continue to Payment
                </button>
            @endif
        </div>
    @endif

    {{-- Payment Step --}}
    @if($currentStep === 'payment')
        <div class="mt-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Payment Method</h3>

            <div class="space-y-3">
                @foreach(['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'] as $value => $label)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-4 hover:border-gray-400 dark:border-gray-700 dark:hover:border-gray-500">
                        <input type="radio" wire:model="selectedPaymentMethod" value="{{ $value }}"
                               class="text-gray-900 focus:ring-gray-500 dark:text-white">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            {{-- Discount Code --}}
            <div class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-800">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Discount Code</h4>
                @if($appliedDiscountCode)
                    <div class="mt-2 flex items-center justify-between rounded-md bg-green-50 px-3 py-2 dark:bg-green-900/20">
                        <span class="text-sm text-green-800 dark:text-green-300">{{ $appliedDiscountCode }} applied</span>
                        <button wire:click="removeDiscount" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Remove</button>
                    </div>
                @else
                    <div class="mt-2 flex gap-2">
                        <input wire:model="discountCode" type="text" placeholder="Enter code"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm">
                        <button wire:click="applyDiscount"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            Apply
                        </button>
                    </div>
                    @if($discountError)
                        <p class="mt-1 text-sm text-red-600">{{ $discountError }}</p>
                    @endif
                @endif
            </div>

            <button wire:click="selectPaymentMethod"
                    @if(!$selectedPaymentMethod) disabled @endif
                    class="w-full rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                Review Order
            </button>
        </div>
    @endif

    {{-- Review Step --}}
    @if($currentStep === 'review')
        <div class="mt-6 space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Summary</h3>

            @if(!empty($totals))
                <div class="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span class="text-gray-900 dark:text-white">{{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span>
                        </div>
                        @if(($totals['discount'] ?? 0) > 0)
                            <div class="flex justify-between text-green-600 dark:text-green-400">
                                <span>Discount</span>
                                <span>-{{ number_format($totals['discount'] / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Shipping</span>
                            <span class="text-gray-900 dark:text-white">{{ number_format(($totals['shipping'] ?? 0) / 100, 2) }}</span>
                        </div>
                        @if(($totals['tax_total'] ?? 0) > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Tax</span>
                                <span class="text-gray-900 dark:text-white">{{ number_format($totals['tax_total'] / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold dark:border-gray-700">
                            <span class="text-gray-900 dark:text-white">Total</span>
                            <span class="text-gray-900 dark:text-white">{{ number_format(($totals['total'] ?? 0) / 100, 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Payment processing will be available in Phase 5. The order will be finalized when the payment endpoint is implemented.
            </p>
        </div>
    @endif
</div>
