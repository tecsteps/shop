<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white mb-8">Checkout</h1>

        {{-- Step indicator --}}
        <nav class="mb-8" aria-label="Checkout steps">
            <ol class="flex items-center gap-2 text-sm">
                @foreach ([1 => 'Contact & Address', 2 => 'Shipping', 3 => 'Payment'] as $step => $label)
                    <li class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="goToStep({{ $step }})"
                            @class([
                                'flex items-center gap-2 font-medium transition-colors',
                                'text-blue-600 dark:text-blue-400' => $currentStep === $step,
                                'text-zinc-900 dark:text-white' => $step < $currentStep,
                                'text-zinc-400 dark:text-zinc-500 cursor-default' => $step > $currentStep,
                            ])
                            @if ($step > $currentStep) disabled @endif
                            @if ($currentStep === $step) aria-current="step" @endif
                        >
                            <span @class([
                                'flex items-center justify-center size-7 rounded-full text-xs font-bold',
                                'bg-blue-600 text-white' => $currentStep === $step,
                                'bg-green-500 text-white' => $step < $currentStep,
                                'bg-zinc-200 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400' => $step > $currentStep,
                            ])>
                                @if ($step < $currentStep)
                                    <flux:icon name="check" class="size-4" />
                                @else
                                    {{ $step }}
                                @endif
                            </span>
                            <span class="hidden sm:inline">{{ $label }}</span>
                        </button>
                        @if ($step < 3)
                            <flux:icon name="chevron-right" class="size-4 text-zinc-400" />
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        @if (session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            {{-- Main content --}}
            <div class="lg:col-span-7">
                {{-- Step 1: Contact & Address --}}
                @if ($currentStep === 1)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Contact Information</h2>

                        <div class="space-y-4">
                            <div>
                                <flux:input
                                    wire:model="email"
                                    label="Email"
                                    type="email"
                                    placeholder="your@email.com"
                                    required
                                />
                                @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mt-8 mb-4">Shipping Address</h2>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <flux:input wire:model="firstName" label="First name" required />
                                    @error('firstName') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <flux:input wire:model="lastName" label="Last name" required />
                                    @error('lastName') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <flux:input wire:model="address1" label="Address" placeholder="Street address" required />
                                @error('address1') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <flux:input wire:model="address2" label="Apartment, suite, etc." placeholder="Optional" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <flux:input wire:model="city" label="City" required />
                                    @error('city') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <flux:input wire:model="province" label="Province / State" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <flux:input wire:model="postalCode" label="Postal code" required />
                                    @error('postalCode') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Country</label>
                                    <select
                                        wire:model="country"
                                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="DE">Germany</option>
                                        <option value="AT">Austria</option>
                                        <option value="CH">Switzerland</option>
                                        <option value="FR">France</option>
                                        <option value="NL">Netherlands</option>
                                        <option value="BE">Belgium</option>
                                        <option value="IT">Italy</option>
                                        <option value="ES">Spain</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="US">United States</option>
                                    </select>
                                    @error('country') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <flux:input wire:model="phone" label="Phone" type="tel" placeholder="Optional" />
                            </div>
                        </div>

                        <div class="mt-6">
                            <flux:button
                                wire:click="continueToShipping"
                                variant="primary"
                                class="w-full justify-center"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="continueToShipping">Continue to Shipping</span>
                                <span wire:loading wire:target="continueToShipping">Processing...</span>
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Step 2: Shipping Method --}}
                @if ($currentStep === 2)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Shipping Method</h2>

                        {{-- Address summary --}}
                        <div class="mb-6 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-sm text-zinc-600 dark:text-zinc-400">
                            <p>{{ $firstName }} {{ $lastName }}</p>
                            <p>{{ $address1 }}@if ($address2), {{ $address2 }}@endif</p>
                            <p>{{ $postalCode }} {{ $city }}, {{ $country }}</p>
                            <button
                                type="button"
                                wire:click="goToStep(1)"
                                class="text-blue-600 dark:text-blue-400 hover:underline mt-1 text-xs"
                            >
                                Change address
                            </button>
                        </div>

                        @if (count($availableShippingRates) > 0)
                            <div class="space-y-2">
                                @foreach ($availableShippingRates as $rate)
                                    <label
                                        class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-colors
                                            {{ $selectedShippingRate === $rate['id'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/10' : 'border-zinc-200 dark:border-zinc-600 hover:border-zinc-400' }}"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="radio"
                                                wire:model.live="selectedShippingRate"
                                                value="{{ $rate['id'] }}"
                                                class="text-blue-600"
                                            />
                                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $rate['name'] }}</span>
                                        </div>
                                        @if ($rate['price'] !== null)
                                            <x-storefront.price :amount="$rate['price']" :currency="$currency" class="text-sm" />
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedShippingRate') <p class="text-sm text-red-600 mt-2">{{ $message }}</p> @enderror
                        @else
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No shipping methods available for your address.</p>
                        @endif

                        <div class="mt-6">
                            <flux:button
                                wire:click="continueToPayment"
                                variant="primary"
                                class="w-full justify-center"
                                wire:loading.attr="disabled"
                                :disabled="!$selectedShippingRate"
                            >
                                <span wire:loading.remove wire:target="continueToPayment">Continue to Payment</span>
                                <span wire:loading wire:target="continueToPayment">Processing...</span>
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Payment --}}
                @if ($currentStep === 3)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Payment</h2>

                        {{-- Payment method selection --}}
                        <div class="space-y-2 mb-6">
                            @foreach ([
                                'credit_card' => ['label' => 'Credit Card', 'icon' => 'credit-card'],
                                'paypal' => ['label' => 'PayPal', 'icon' => 'banknotes'],
                                'bank_transfer' => ['label' => 'Bank Transfer', 'icon' => 'building-library'],
                            ] as $method => $info)
                                <label
                                    class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors
                                        {{ $paymentMethod === $method ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/10' : 'border-zinc-200 dark:border-zinc-600 hover:border-zinc-400' }}"
                                >
                                    <input
                                        type="radio"
                                        wire:model.live="paymentMethod"
                                        value="{{ $method }}"
                                        class="text-blue-600"
                                    />
                                    <flux:icon name="{{ $info['icon'] }}" class="size-5 text-zinc-600 dark:text-zinc-400" />
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $info['label'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        {{-- Credit card form (mock) --}}
                        @if ($paymentMethod === 'credit_card')
                            <div class="space-y-4 p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                                <flux:input
                                    wire:model="cardNumber"
                                    label="Card number"
                                    placeholder="1234 5678 9012 3456"
                                    maxlength="19"
                                />
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input
                                        wire:model="cardExpiry"
                                        label="Expiry date"
                                        placeholder="MM/YY"
                                        maxlength="5"
                                    />
                                    <flux:input
                                        wire:model="cardCvv"
                                        label="CVV"
                                        placeholder="123"
                                        maxlength="4"
                                    />
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">This is a mock payment form for testing.</p>
                            </div>
                        @elseif ($paymentMethod === 'paypal')
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">You will be redirected to PayPal to complete your payment.</p>
                            </div>
                        @elseif ($paymentMethod === 'bank_transfer')
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white mb-2">Bank Transfer Instructions</p>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                                    <p>Bank: Demo Bank</p>
                                    <p>IBAN: DE89 3704 0044 0532 0130 00</p>
                                    <p>BIC: COBADEFFXXX</p>
                                    <p class="mt-2 text-xs">Please include your order number as the payment reference.</p>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6">
                            <flux:button
                                wire:click="placeOrder"
                                variant="primary"
                                class="w-full justify-center"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="placeOrder">Place Order</span>
                                <span wire:loading wire:target="placeOrder">Processing order...</span>
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Order summary sidebar --}}
            <div class="lg:col-span-5 mt-8 lg:mt-0">
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 lg:sticky lg:top-24">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Order Summary</h2>

                    <ul class="space-y-3 mb-4">
                        @foreach ($lines as $line)
                            <li wire:key="checkout-line-{{ $line->id }}" class="flex gap-3">
                                <div class="shrink-0 size-14 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 relative">
                                    @if ($line->variant?->product?->media?->first())
                                        <img
                                            src="{{ $line->variant->product->media->first()->url }}"
                                            alt="{{ $line->variant->product->title }}"
                                            class="size-full object-cover"
                                        />
                                    @else
                                        <div class="size-full flex items-center justify-center">
                                            <flux:icon name="shopping-bag" class="size-5 text-zinc-400" />
                                        </div>
                                    @endif
                                    <span class="absolute -top-1 -right-1 size-5 bg-zinc-500 text-white text-xs rounded-full flex items-center justify-center">
                                        {{ $line->quantity }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                        {{ $line->variant?->product?->title ?? 'Product' }}
                                    </p>
                                    @if ($line->variant?->title && $line->variant->title !== 'Default')
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant->title }}</p>
                                    @endif
                                </div>
                                <x-storefront.price :amount="$line->line_total_amount" :currency="$currency" class="text-sm shrink-0" />
                            </li>
                        @endforeach
                    </ul>

                    <dl class="space-y-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                            <dd><x-storefront.price :amount="$subtotal" :currency="$currency" class="text-sm" /></dd>
                        </div>

                        @if ($checkout->discount_code)
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Discount ({{ $checkout->discount_code }})
                                </dt>
                                <dd class="text-sm text-green-600 dark:text-green-400">
                                    @if ($totals && isset($totals['discount']))
                                        -<x-storefront.price :amount="$totals['discount']" :currency="$currency" class="text-sm" />
                                    @else
                                        Calculated
                                    @endif
                                </dd>
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">Shipping</dt>
                            <dd class="text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($totals && isset($totals['shipping']))
                                    <x-storefront.price :amount="$totals['shipping']" :currency="$currency" class="text-sm" />
                                @else
                                    Calculated at next step
                                @endif
                            </dd>
                        </div>

                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">Tax</dt>
                            <dd class="text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($totals && isset($totals['tax_total']))
                                    <x-storefront.price :amount="$totals['tax_total']" :currency="$currency" class="text-sm" />
                                @else
                                    Calculated at next step
                                @endif
                            </dd>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                            <dt class="text-base font-semibold text-zinc-900 dark:text-white">Total</dt>
                            <dd>
                                @if ($totals && isset($totals['total']))
                                    <x-storefront.price :amount="$totals['total']" :currency="$currency" class="text-base" />
                                @else
                                    <x-storefront.price :amount="$subtotal" :currency="$currency" class="text-base" />
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
