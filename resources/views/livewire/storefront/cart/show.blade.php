<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white mb-8">Shopping Cart</h1>

        @if ($lines->isEmpty())
            <div class="text-center py-16">
                <flux:icon name="shopping-bag" class="size-20 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                <p class="text-zinc-500 dark:text-zinc-400 mb-4">Your cart is empty.</p>
                <flux:button href="{{ route('storefront.home') }}" variant="primary" wire:navigate>
                    Continue Shopping
                </flux:button>
            </div>
        @else
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                {{-- Cart items --}}
                <div class="lg:col-span-8">
                    <div class="space-y-4">
                        @foreach ($lines as $line)
                            <div wire:key="cart-line-{{ $line->id }}" class="flex gap-4 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                {{-- Product image --}}
                                <div class="shrink-0 size-24 sm:size-28 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                    @if ($line->variant?->product?->media?->first())
                                        <img
                                            src="{{ $line->variant->product->media->first()->url }}"
                                            alt="{{ $line->variant->product->title }}"
                                            class="size-full object-cover"
                                        />
                                    @else
                                        <div class="size-full flex items-center justify-center">
                                            <flux:icon name="shopping-bag" class="size-8 text-zinc-400" />
                                        </div>
                                    @endif
                                </div>

                                {{-- Product info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-zinc-900 dark:text-white">
                                                {{ $line->variant?->product?->title ?? 'Product' }}
                                            </h3>
                                            @if ($line->variant?->title && $line->variant->title !== 'Default')
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $line->variant->title }}</p>
                                            @endif
                                            <div class="mt-1">
                                                <x-storefront.price :amount="$line->unit_price_amount" :currency="$currency" class="text-sm" />
                                            </div>
                                        </div>
                                        <x-storefront.price :amount="$line->line_total_amount" :currency="$currency" class="text-sm" />
                                    </div>

                                    {{-- Quantity controls --}}
                                    <div class="flex items-center gap-3 mt-3">
                                        <div class="inline-flex items-center border border-zinc-300 dark:border-zinc-600 rounded-lg">
                                            <button
                                                type="button"
                                                wire:click="updateQuantity({{ $line->id }}, {{ max(0, $line->quantity - 1) }})"
                                                class="flex items-center justify-center w-9 h-9 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                                aria-label="Decrease quantity"
                                            >
                                                <flux:icon name="minus" class="size-4" />
                                            </button>
                                            <span class="w-10 text-center text-sm font-medium text-zinc-900 dark:text-white border-x border-zinc-300 dark:border-zinc-600 h-9 flex items-center justify-center">
                                                {{ $line->quantity }}
                                            </span>
                                            <button
                                                type="button"
                                                wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                                class="flex items-center justify-center w-9 h-9 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                                aria-label="Increase quantity"
                                            >
                                                <flux:icon name="plus" class="size-4" />
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="removeItem({{ $line->id }})"
                                            class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Discount code --}}
                    <div class="mt-6 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white mb-3">Discount Code</h3>
                        <div class="flex gap-2">
                            <flux:input
                                wire:model="discountCode"
                                placeholder="Enter discount code"
                                class="flex-1"
                            />
                            <flux:button
                                wire:click="applyDiscount"
                                wire:loading.attr="disabled"
                            >
                                Apply
                            </flux:button>
                        </div>
                        @if ($discountError)
                            <p class="text-sm text-red-600 dark:text-red-400 mt-2">{{ $discountError }}</p>
                        @endif
                        @if ($discountSuccess)
                            <p class="text-sm text-green-600 dark:text-green-400 mt-2">{{ $discountSuccess }}</p>
                        @endif
                    </div>
                </div>

                {{-- Order summary sidebar --}}
                <div class="lg:col-span-4 mt-8 lg:mt-0">
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 lg:sticky lg:top-24">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Order Summary</h2>

                        <dl class="space-y-3">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-zinc-600 dark:text-zinc-400">Subtotal ({{ $itemCount }} {{ $itemCount === 1 ? 'item' : 'items' }})</dt>
                                <dd><x-storefront.price :amount="$subtotal" :currency="$currency" class="text-sm" /></dd>
                            </div>

                            {{-- Shipping estimate --}}
                            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white mb-2">Estimate Shipping</h3>
                                <select
                                    wire:model.live="shippingCountry"
                                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Select country</option>
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

                                @if (count($shippingRates) > 0)
                                    <ul class="mt-2 space-y-1">
                                        @foreach ($shippingRates as $rate)
                                            <li class="flex items-center justify-between text-sm py-1">
                                                <span class="text-zinc-600 dark:text-zinc-400">{{ $rate['name'] }}</span>
                                                @if ($rate['price'] !== null)
                                                    <x-storefront.price :amount="$rate['price']" :currency="$currency" class="text-sm" />
                                                @else
                                                    <span class="text-zinc-400 text-xs">N/A</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif ($shippingCountry)
                                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">No shipping rates available for this country.</p>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                                <dt class="text-sm text-zinc-600 dark:text-zinc-400">Estimated Tax</dt>
                                <dd class="text-sm text-zinc-500 dark:text-zinc-400">Calculated at checkout</dd>
                            </div>

                            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                                <dt class="text-base font-semibold text-zinc-900 dark:text-white">Estimated Total</dt>
                                <dd><x-storefront.price :amount="$subtotal" :currency="$currency" class="text-base" /></dd>
                            </div>
                        </dl>

                        <flux:button
                            wire:click="proceedToCheckout"
                            variant="primary"
                            class="w-full justify-center mt-6"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="proceedToCheckout">Proceed to Checkout</span>
                            <span wire:loading wire:target="proceedToCheckout">Creating checkout...</span>
                        </flux:button>

                        <a
                            href="{{ route('storefront.home') }}"
                            class="block text-center text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 mt-3"
                            wire:navigate
                        >
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
