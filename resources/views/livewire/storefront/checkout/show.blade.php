<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Checkout</h1>

        <div class="mt-8 lg:grid lg:grid-cols-5 lg:gap-12">
            {{-- Left column: checkout steps --}}
            <div class="lg:col-span-3 space-y-6">

                {{-- Step 1: Contact --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">1. Contact information</h2>
                        @if($currentStep > 1)
                            <button wire:click="editStep(1)" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Edit</button>
                        @endif
                    </div>

                    @if($currentStep === 1)
                        <div class="mt-4">
                            <flux:input wire:model="email" label="Email" type="email" required />
                            @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Already have an account? <a href="{{ route('customer.login') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Log in</a>
                            </p>
                            <div class="mt-4">
                                <flux:button wire:click="continueToAddress" variant="primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="continueToAddress">Continue</span>
                                    <span wire:loading wire:target="continueToAddress">Loading...</span>
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $email }}</p>
                    @endif
                </div>

                {{-- Step 2: Shipping Address --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800 {{ $currentStep < 2 ? 'opacity-50' : '' }}">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">2. Shipping address</h2>
                        @if($currentStep > 2)
                            <button wire:click="editStep(2)" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Edit</button>
                        @endif
                    </div>

                    @if($currentStep === 2)
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <flux:input wire:model="firstName" label="First name" required />
                                @error('firstName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="lastName" label="Last name" required />
                                @error('lastName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <flux:input wire:model="address1" label="Address" required />
                                @error('address1') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <flux:input wire:model="address2" label="Address line 2" />
                            </div>
                            <div>
                                <flux:input wire:model="city" label="City" required />
                                @error('city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="province" label="State / Province" required />
                                @error('province') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="postalCode" label="Postal code" required />
                                @error('postalCode') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="country" label="Country" required />
                                @error('country') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <flux:input wire:model="phone" label="Phone" type="tel" />
                            </div>

                            <div class="sm:col-span-2">
                                <flux:checkbox wire:model="billingSameAsShipping" label="Billing address same as shipping" />
                            </div>

                            @if(!$billingSameAsShipping)
                                <div class="sm:col-span-2">
                                    <h3 class="mb-4 text-base font-medium text-gray-900 dark:text-white">Billing address</h3>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <flux:input wire:model="billingFirstName" label="First name" required />
                                        <flux:input wire:model="billingLastName" label="Last name" required />
                                        <div class="sm:col-span-2">
                                            <flux:input wire:model="billingAddress1" label="Address" required />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <flux:input wire:model="billingAddress2" label="Address line 2" />
                                        </div>
                                        <flux:input wire:model="billingCity" label="City" required />
                                        <flux:input wire:model="billingProvince" label="State / Province" required />
                                        <flux:input wire:model="billingPostalCode" label="Postal code" required />
                                        <flux:input wire:model="billingCountry" label="Country" required />
                                    </div>
                                </div>
                            @endif

                            <div class="sm:col-span-2 mt-2">
                                <flux:button wire:click="continueToShipping" variant="primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="continueToShipping">Continue</span>
                                    <span wire:loading wire:target="continueToShipping">Loading...</span>
                                </flux:button>
                            </div>
                        </div>
                    @elseif($currentStep > 2)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ $firstName }} {{ $lastName }}, {{ $address1 }}, {{ $city }}, {{ $province }} {{ $postalCode }}
                        </p>
                    @endif
                </div>

                {{-- Step 3: Shipping Method --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800 {{ $currentStep < 3 ? 'opacity-50' : '' }}">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">3. Shipping method</h2>
                        @if($currentStep > 3)
                            <button wire:click="editStep(3)" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Edit</button>
                        @endif
                    </div>

                    @if($currentStep === 3)
                        <div class="mt-4 space-y-3">
                            @if(count($availableShippingRates) === 0)
                                <p class="text-sm text-amber-600 dark:text-amber-400">No shipping methods are available for your address. Please verify your address or contact us.</p>
                            @else
                                <fieldset>
                                    <legend class="sr-only">Shipping method</legend>
                                    @foreach($availableShippingRates as $rate)
                                        <label wire:key="rate-{{ $rate['id'] }}"
                                               class="flex cursor-pointer items-center justify-between rounded-lg border p-4 transition {{ $selectedShippingRateId === $rate['id'] ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' }}">
                                            <div class="flex items-center gap-3">
                                                <input type="radio" wire:model="selectedShippingRateId" value="{{ $rate['id'] }}" class="text-blue-600 focus:ring-blue-500">
                                                <div>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rate['name'] }}</span>
                                                    @if($rate['description'])
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rate['description'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                @if($rate['amount'] === 0)
                                                    Free
                                                @else
                                                    ${{ number_format($rate['amount'] / 100, 2) }}
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </fieldset>
                                <div class="mt-4">
                                    <flux:button wire:click="continueToPayment" variant="primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="continueToPayment">Continue</span>
                                        <span wire:loading wire:target="continueToPayment">Loading...</span>
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    @elseif($currentStep > 3)
                        @php
                            $selectedRate = collect($availableShippingRates)->firstWhere('id', $selectedShippingRateId);
                        @endphp
                        @if($selectedRate)
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $selectedRate['name'] }}
                                - @if($selectedRate['amount'] === 0) Free @else ${{ number_format($selectedRate['amount'] / 100, 2) }} @endif
                            </p>
                        @endif
                    @endif
                </div>

                {{-- Step 4: Payment --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800 {{ $currentStep < 4 ? 'opacity-50' : '' }}">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">4. Payment method</h2>

                    @if($currentStep === 4)
                        <div class="mt-4 space-y-4">
                            {{-- Payment method selection --}}
                            <fieldset class="space-y-3">
                                <legend class="sr-only">Payment method</legend>
                                @foreach(['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'] as $method => $label)
                                    <label wire:key="payment-{{ $method }}"
                                           class="flex cursor-pointer items-center rounded-lg border p-4 transition {{ $paymentMethod === $method ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' }}">
                                        <input type="radio" wire:model.live="paymentMethod" value="{{ $method }}" class="text-blue-600 focus:ring-blue-500">
                                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </fieldset>

                            {{-- Credit card form --}}
                            @if($paymentMethod === 'credit_card')
                                <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                    <flux:input wire:model="cardNumber" label="Card number" placeholder="4242 4242 4242 4242" required />
                                    @error('cardNumber') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    <flux:input wire:model="cardholderName" label="Cardholder name" required />
                                    @error('cardholderName') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <flux:input wire:model="cardExpiry" label="Expiry" placeholder="MM/YY" required />
                                            @error('cardExpiry') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <flux:input wire:model="cardCvc" label="CVC" placeholder="123" required />
                                            @error('cardCvc') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            @elseif($paymentMethod === 'paypal')
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Your PayPal payment will be processed securely.</p>
                                </div>
                            @elseif($paymentMethod === 'bank_transfer')
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.</p>
                                </div>
                            @endif

                            {{-- Payment error --}}
                            @if($paymentError)
                                <flux:callout variant="danger">
                                    <p>Payment declined: {{ $paymentError }}</p>
                                </flux:callout>
                            @endif

                            {{-- Pay button --}}
                            <flux:button wire:click="pay" variant="primary" class="w-full" wire:loading.attr="disabled" :disabled="$processing">
                                <span wire:loading.remove wire:target="pay">
                                    @if($paymentMethod === 'bank_transfer')
                                        Place order
                                    @elseif($paymentMethod === 'paypal')
                                        Pay with PayPal
                                    @else
                                        Pay now
                                    @endif
                                    @if(isset($totals['total']))
                                        - ${{ number_format(($totals['total'] ?? 0) / 100, 2) }}
                                    @endif
                                </span>
                                <span wire:loading wire:target="pay">Processing...</span>
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right column: Order Summary --}}
            <div class="mt-8 lg:col-span-2 lg:mt-0">
                <div class="sticky top-24 rounded-lg bg-gray-50 p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order Summary</h2>

                    {{-- Cart items --}}
                    <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($cartLines as $line)
                            <div wire:key="checkout-line-{{ $line['id'] }}" class="flex items-center justify-between py-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $line['title'] }}</p>
                                    @if($line['variant_title'] && $line['variant_title'] !== 'Default')
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $line['variant_title'] }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ $line['quantity'] }}</p>
                                </div>
                                <span class="ml-4 text-sm font-medium text-gray-900 dark:text-white">${{ number_format($line['total'] / 100, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Discount code --}}
                    <div class="mt-4 flex gap-2">
                        <flux:input wire:model="discountCode" placeholder="Discount code" class="flex-1" />
                        <flux:button wire:click="applyDiscount" wire:loading.attr="disabled">Apply</flux:button>
                    </div>

                    {{-- Totals --}}
                    <div class="mt-4 space-y-2 border-t border-gray-200 pt-4 dark:border-gray-700" aria-live="polite">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                            <span class="text-gray-900 dark:text-white">${{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span>
                        </div>
                        @if(($totals['discount'] ?? 0) > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Discount</span>
                                <span class="text-green-600 dark:text-green-400">-${{ number_format(($totals['discount'] ?? 0) / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                            <span class="text-gray-900 dark:text-white">
                                @if($currentStep < 3)
                                    Calculated at next step
                                @else
                                    ${{ number_format(($totals['shipping'] ?? 0) / 100, 2) }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Tax</span>
                            <span class="text-gray-900 dark:text-white">
                                @if($currentStep < 2)
                                    Calculated after address
                                @else
                                    ${{ number_format(($totals['tax_total'] ?? $totals['tax'] ?? 0) / 100, 2) }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                            <span class="text-base font-semibold text-gray-900 dark:text-white">Total</span>
                            <span class="text-base font-semibold text-gray-900 dark:text-white">${{ number_format(($totals['total'] ?? 0) / 100, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
