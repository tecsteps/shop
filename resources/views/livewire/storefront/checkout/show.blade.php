<div class="mx-auto max-w-7xl px-4 py-8">
    <h1 class="text-3xl font-bold">Checkout</h1>

    <div class="mt-6 grid gap-8 lg:grid-cols-5">
        <div class="flex flex-col gap-6 lg:col-span-3">
            <section aria-label="Contact information" class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
                <h2 class="text-lg font-semibold">1. Contact information</h2>
                @if ($step > 1)
                    <p class="mt-2 text-sm">{{ $checkout->email }}</p>
                @else
                    <div class="mt-4">
                        <flux:input wire:model="email" type="email" :label="__('Email')" required autocomplete="email" />
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @if ($isGuest)
                            <p class="mt-2 text-sm"><a href="{{ route('customer.login') }}" class="text-blue-600 hover:underline dark:text-blue-400">Already have an account? Log in</a></p>
                        @endif
                        <flux:button wire:click="saveContact" variant="ghost" wire:loading.attr="disabled" class="mt-3">Continue</flux:button>
                    </div>
                @endif
            </section>

            <section aria-label="Shipping address" class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
                <h2 class="text-lg font-semibold">2. Shipping address</h2>
                @if ($step > 1)
                    @php $a = (array) $checkout->shipping_address_json; @endphp
                    <p class="mt-2 text-sm">{{ $a['first_name'] ?? '' }} {{ $a['last_name'] ?? '' }}, {{ $a['address1'] ?? '' }}, {{ $a['city'] ?? '' }} {{ $a['postal_code'] ?? '' }}, {{ $a['country_code'] ?? '' }}</p>
                @else
                    <div class="mt-4 flex flex-col gap-4">
                        @if ($savedAddresses->isNotEmpty())
                            <div>
                                <label for="saved-address" class="mb-1 block text-sm font-medium">Select a saved address</label>
                                <select id="saved-address" wire:model.live="savedAddressId" wire:change="selectSavedAddress" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                    <option value="">Use a new address</option>
                                    @foreach ($savedAddresses as $saved)
                                        <option value="{{ $saved->getKey() }}">{{ $saved->label ?? 'Address' }} - {{ $saved->address_json['address1'] ?? '' }}, {{ $saved->address_json['city'] ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <x-storefront.address-form prefix="shipping" />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="useShippingAsBilling" class="h-4 w-4" />
                            Billing address same as shipping
                        </label>
                        @if (! $useShippingAsBilling)
                            <h3 class="font-semibold">Billing address</h3>
                            <x-storefront.address-form prefix="billing" />
                        @endif
                        <flux:button wire:click="saveAddress" variant="primary" wire:loading.attr="disabled">Continue</flux:button>
                    </div>
                @endif
            </section>

            <section aria-label="Shipping method" class="rounded-lg border border-zinc-200 p-6 {{ $step < 3 ? 'opacity-60' : '' }} dark:border-zinc-800">
                <h2 class="text-lg font-semibold">3. Shipping method</h2>
                @if ($step < 3)
                    <p class="mt-2 text-sm text-zinc-500">Complete the previous steps first.</p>
                @elseif (! $requiresShipping)
                    <p class="mt-2 text-sm">No shipping required for this order.</p>
                @elseif ($shippingOptions->isEmpty())
                    <p class="mt-2 text-sm text-amber-600">No shipping methods are available for your address. Please verify your address or contact us.</p>
                @else
                    <fieldset class="mt-4 flex flex-col gap-2">
                        <legend class="sr-only">Shipping method</legend>
                        @foreach ($shippingOptions as $option)
                            <label wire:key="ship-{{ $option->id }}" class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border p-4 {{ (int) $shippingRateId === (int) $option->id ? 'border-blue-600 bg-blue-50 dark:bg-blue-950' : 'border-zinc-300 dark:border-zinc-700' }}">
                                <span class="flex items-center gap-3">
                                    <input type="radio" wire:model.live="shippingRateId" value="{{ $option->id }}" class="h-4 w-4" />
                                    <span class="text-sm font-medium">{{ $option->name }}</span>
                                </span>
                                <span class="text-sm">{{ \App\Support\Money::format($option->amount ?? 0, $currency) }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('shippingRateId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($step === 3)
                        <flux:button wire:click="saveShipping" variant="primary" wire:loading.attr="disabled" class="mt-3">Continue</flux:button>
                    @endif
                @endif
            </section>

            <section aria-label="Payment method" class="rounded-lg border border-zinc-200 p-6 {{ $step < 4 ? 'opacity-60' : '' }} dark:border-zinc-800">
                <h2 class="text-lg font-semibold">4. Payment method</h2>
                @if ($step < 4)
                    <p class="mt-2 text-sm text-zinc-500">Complete the previous steps first.</p>
                @else
                    <fieldset class="mt-4 flex flex-col gap-2">
                        <legend class="sr-only">Payment method</legend>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 {{ $paymentMethod === 'credit_card' ? 'border-blue-600 bg-blue-50 dark:bg-blue-950' : 'border-zinc-300 dark:border-zinc-700' }}">
                            <input type="radio" wire:model.live="paymentMethod" value="credit_card" class="h-4 w-4" />
                            <span class="text-sm font-medium">Credit Card</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 {{ $paymentMethod === 'paypal' ? 'border-blue-600 bg-blue-50 dark:bg-blue-950' : 'border-zinc-300 dark:border-zinc-700' }}">
                            <input type="radio" wire:model.live="paymentMethod" value="paypal" class="h-4 w-4" />
                            <span class="text-sm font-medium">PayPal</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 {{ $paymentMethod === 'bank_transfer' ? 'border-blue-600 bg-blue-50 dark:bg-blue-950' : 'border-zinc-300 dark:border-zinc-700' }}">
                            <input type="radio" wire:model.live="paymentMethod" value="bank_transfer" class="h-4 w-4" />
                            <span class="text-sm font-medium">Bank Transfer</span>
                        </label>
                    </fieldset>

                    @if ($paymentMethod === 'credit_card')
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <flux:input wire:model="cardNumber" :label="__('Card number')" required autocomplete="cc-number" placeholder="4242 4242 4242 4242" />
                                @error('cardNumber') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <flux:input wire:model="cardHolder" :label="__('Cardholder name')" required autocomplete="cc-name" />
                                @error('cardHolder') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="cardExpiry" :label="__('Expiry')" required placeholder="12/28" autocomplete="cc-exp" />
                                @error('cardExpiry') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="cardCvc" :label="__('CVC')" required autocomplete="cc-csc" />
                                @error('cardCvc') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @elseif ($paymentMethod === 'paypal')
                        <p class="mt-4 text-sm">Your PayPal payment will be processed securely.</p>
                    @else
                        <p class="mt-4 text-sm">After placing your order, you will receive bank transfer instructions. Your order will be held for 7 days while we await your payment.</p>
                    @endif

                    @if ($payError)
                        <flux:callout variant="danger" icon="x-circle" heading="{{ $payError }}" class="mt-4" />
                    @endif

                    <flux:button wire:click="pay" variant="primary" wire:loading.attr="disabled" class="mt-4 w-full">
                        <span wire:loading.remove wire:target="pay">
                            @if ($paymentMethod === 'paypal')
                                Pay with PayPal - {{ \App\Support\Money::format($summaryTotals['total'], $currency) }}
                            @elseif ($paymentMethod === 'bank_transfer')
                                Place order - {{ \App\Support\Money::format($summaryTotals['total'], $currency) }}
                            @else
                                Pay now - {{ \App\Support\Money::format($summaryTotals['total'], $currency) }}
                            @endif
                        </span>
                        <span wire:loading wire:target="pay">Processing...</span>
                    </flux:button>
                @endif
            </section>
        </div>

        <div class="lg:col-span-2">
            <div class="lg:sticky lg:top-24">
                <x-storefront.order-summary :lines="$summaryLines" :totals="$summaryTotals" :currency="$currency" :discountCode="$checkout->discount_code" :showDiscountInput="true" />
            </div>
        </div>
    </div>
</div>
