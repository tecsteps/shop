<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">Checkout</h1>

    <div class="lg:grid lg:grid-cols-5 lg:gap-12">
        {{-- Checkout Form --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Step 1: Contact --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 flex items-center justify-between cursor-pointer"
                     @if($currentStep !== 1) wire:click="goToStep(1)" @endif>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">1. Contact information</h2>
                    @if($currentStep > 1)
                        <span class="text-xs text-blue-600 dark:text-blue-400">Edit</span>
                    @endif
                </div>
                @if($currentStep === 1)
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" wire:model="email" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="completeStep1" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors">
                            Continue to shipping
                        </button>
                    </div>
                @elseif($currentStep > 1)
                    <div class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $email }}</div>
                @endif
            </div>

            {{-- Step 2: Shipping Address --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden {{ $currentStep < 2 ? 'opacity-50' : '' }}">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 flex items-center justify-between {{ $currentStep > 2 ? 'cursor-pointer' : '' }}"
                     @if($currentStep > 2) wire:click="goToStep(2)" @endif>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">2. Shipping address</h2>
                    @if($currentStep > 2)
                        <span class="text-xs text-blue-600 dark:text-blue-400">Edit</span>
                    @endif
                </div>
                @if($currentStep === 2)
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="shippingFirstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First name <span class="text-red-500">*</span></label>
                                <input type="text" id="shippingFirstName" wire:model="shippingFirstName" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                @error('shippingFirstName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="shippingLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last name <span class="text-red-500">*</span></label>
                                <input type="text" id="shippingLastName" wire:model="shippingLastName" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                @error('shippingLastName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label for="shippingAddress1" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address <span class="text-red-500">*</span></label>
                            <input type="text" id="shippingAddress1" wire:model="shippingAddress1" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            @error('shippingAddress1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="shippingAddress2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Apartment, suite, etc.</label>
                            <input type="text" id="shippingAddress2" wire:model="shippingAddress2"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="shippingCity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City <span class="text-red-500">*</span></label>
                                <input type="text" id="shippingCity" wire:model="shippingCity" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                @error('shippingCity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="shippingZip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Postal code <span class="text-red-500">*</span></label>
                                <input type="text" id="shippingZip" wire:model="shippingZip" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                @error('shippingZip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="shippingCountry" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country <span class="text-red-500">*</span></label>
                                <select id="shippingCountry" wire:model="shippingCountry"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="DE">Germany</option>
                                    <option value="AT">Austria</option>
                                    <option value="CH">Switzerland</option>
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="FR">France</option>
                                    <option value="NL">Netherlands</option>
                                </select>
                                @error('shippingCountry') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="shippingPhone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                                <input type="tel" id="shippingPhone" wire:model="shippingPhone"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button wire:click="goToStep(1)" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                Back
                            </button>
                            <button wire:click="completeStep2" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors">
                                Continue to shipping method
                            </button>
                        </div>
                    </div>
                @elseif($currentStep > 2)
                    <div class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ $shippingFirstName }} {{ $shippingLastName }}, {{ $shippingAddress1 }}, {{ $shippingCity }} {{ $shippingZip }}
                    </div>
                @endif
            </div>

            {{-- Step 3: Shipping Method --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden {{ $currentStep < 3 ? 'opacity-50' : '' }}">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 flex items-center justify-between {{ $currentStep > 3 ? 'cursor-pointer' : '' }}"
                     @if($currentStep > 3) wire:click="goToStep(3)" @endif>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">3. Shipping method</h2>
                    @if($currentStep > 3)
                        <span class="text-xs text-blue-600 dark:text-blue-400">Edit</span>
                    @endif
                </div>
                @if($currentStep === 3)
                    <div class="p-4 space-y-3">
                        @if(empty($availableRates))
                            <p class="text-sm text-amber-600 dark:text-amber-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                No shipping methods available for your address.
                            </p>
                        @else
                            <fieldset>
                                <legend class="sr-only">Shipping method</legend>
                                @foreach($availableRates as $rate)
                                    <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer mb-2 transition-colors
                                                  {{ $selectedShippingRateId === $rate['id'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" wire:model.live="selectedShippingRateId" value="{{ $rate['id'] }}" class="text-blue-600 focus:ring-blue-500">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rate['name'] }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $rate['amount'] === 0 ? 'Free' : \App\Support\MoneyFormatter::format($rate['amount']) }}
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @endif
                        <div class="flex gap-3 pt-2">
                            <button wire:click="goToStep(2)" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                Back
                            </button>
                            <button wire:click="completeStep3" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors"
                                    @if(!$selectedShippingRateId && !empty($availableRates)) disabled @endif>
                                Continue to payment
                            </button>
                        </div>
                    </div>
                @elseif($currentStep > 3)
                    @php
                        $selectedRate = collect($availableRates)->firstWhere('id', $selectedShippingRateId);
                    @endphp
                    <div class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ $selectedRate['name'] ?? 'No shipping' }} - {{ ($selectedRate['amount'] ?? 0) === 0 ? 'Free' : \App\Support\MoneyFormatter::format($selectedRate['amount'] ?? 0) }}
                    </div>
                @endif
            </div>

            {{-- Step 4: Payment --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden {{ $currentStep < 4 ? 'opacity-50' : '' }}">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">4. Payment</h2>
                </div>
                @if($currentStep === 4)
                    <div class="p-4 space-y-4">
                        {{-- Payment method selection --}}
                        <fieldset>
                            <legend class="sr-only">Payment method</legend>
                            @foreach(['credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer'] as $method => $label)
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer mb-2 transition-colors
                                              {{ $paymentMethod === $method ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="paymentMethod" value="{{ $method }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                                </label>
                            @endforeach
                        </fieldset>

                        {{-- Credit Card fields --}}
                        @if($paymentMethod === 'credit_card')
                            <div class="space-y-3 pt-2">
                                <div>
                                    <label for="cardNumber" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Card number</label>
                                    <input type="text" id="cardNumber" wire:model="cardNumber" placeholder="4242 4242 4242 4242"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                    @error('cardNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="cardName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cardholder name</label>
                                    <input type="text" id="cardName" wire:model="cardName"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                    @error('cardName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="cardExpiry" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expiry</label>
                                        <input type="text" id="cardExpiry" wire:model="cardExpiry" placeholder="MM/YY"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                        @error('cardExpiry') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="cardCvc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CVC</label>
                                        <input type="text" id="cardCvc" wire:model="cardCvc" placeholder="123"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                        @error('cardCvc') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        @elseif($paymentMethod === 'paypal')
                            <p class="text-sm text-gray-600 dark:text-gray-400 pt-2">Your PayPal payment will be processed securely.</p>
                        @elseif($paymentMethod === 'bank_transfer')
                            <p class="text-sm text-gray-600 dark:text-gray-400 pt-2">After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.</p>
                        @endif

                        @if($paymentError)
                            <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-sm text-red-700 dark:text-red-300">{{ $paymentError }}</p>
                            </div>
                        @endif

                        @php
                            $totalAmount = $totals['total'] ?? $subtotal;
                            $buttonLabel = match($paymentMethod) {
                                'paypal' => 'Pay with PayPal',
                                'bank_transfer' => 'Place order',
                                default => 'Pay now',
                            };
                        @endphp

                        <div class="flex gap-3 pt-2">
                            <button wire:click="goToStep(3)" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                Back
                            </button>
                            <button wire:click="pay"
                                    wire:loading.attr="disabled"
                                    class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold rounded-lg text-sm transition-colors">
                                <span wire:loading.remove wire:target="pay">{{ $buttonLabel }} - {{ \App\Support\MoneyFormatter::format($totalAmount) }}</span>
                                <span wire:loading wire:target="pay">Processing...</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Order Summary Sidebar --}}
        <div class="lg:col-span-2 mt-8 lg:mt-0">
            <div class="lg:sticky lg:top-24 bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Summary</h2>

                <div class="space-y-3 mb-4">
                    @foreach($lines as $line)
                        <div class="flex gap-3">
                            <div class="relative w-12 h-12 bg-gray-200 dark:bg-gray-800 rounded overflow-hidden flex-shrink-0">
                                @if($line->variant?->product?->media?->first())
                                    <img src="{{ $line->variant->product->media->sortBy('position')->first()->storage_key }}" alt="" class="w-full h-full object-cover">
                                @endif
                                <span class="absolute -top-1 -right-1 bg-gray-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $line->quantity }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $line->product_title }}</p>
                                @if($line->variant_title)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $line->variant_title }}</p>
                                @endif
                            </div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ \App\Support\MoneyFormatter::format($line->line_total_amount) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 dark:border-gray-800 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($subtotal) }}</span>
                    </div>
                    @if(($totals['discount'] ?? 0) > 0)
                        <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                            <span>Discount</span>
                            <span>-{{ \App\Support\MoneyFormatter::format($totals['discount']) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Shipping</span>
                        <span class="text-gray-900 dark:text-white">
                            @if(isset($totals['shipping']))
                                {{ $totals['shipping'] === 0 ? 'Free' : \App\Support\MoneyFormatter::format($totals['shipping']) }}
                            @else
                                Calculated at next step
                            @endif
                        </span>
                    </div>
                    @if(isset($totals['tax_total']) && $totals['tax_total'] > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Tax</span>
                            <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($totals['tax_total']) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-200 dark:border-gray-800">
                        <span class="text-gray-900 dark:text-white">Total</span>
                        <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($totals['total'] ?? $subtotal) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
