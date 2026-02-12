<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-8">Your Cart</h1>

    @if($lines->isEmpty())
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <p class="mt-4 text-gray-500 dark:text-gray-400">Your cart is empty</p>
            <a href="{{ route('storefront.collections.index') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                Continue shopping
            </a>
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3" colspan="2">Product</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3">Price</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3">Quantity</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3">Total</th>
                        <th class="pb-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($lines as $line)
                        <tr wire:key="line-{{ $line->id }}">
                            <td class="py-4 pr-4 w-16">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                                    @if($line->variant?->product?->media?->first())
                                        <img src="{{ $line->variant->product->media->sortBy('position')->first()->storage_key }}" alt="" class="w-full h-full object-cover">
                                    @endif
                                </div>
                            </td>
                            <td class="py-4">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $line->product_title }}</p>
                                @if($line->variant_title)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $line->variant_title }}</p>
                                @endif
                            </td>
                            <td class="py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ \App\Support\MoneyFormatter::format($line->unit_price_amount) }}
                            </td>
                            <td class="py-4">
                                <div class="inline-flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                                    <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50"
                                            @if($line->quantity <= 1) disabled @endif
                                            aria-label="Decrease quantity">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    </button>
                                    <span class="w-10 text-center text-sm font-medium text-gray-900 dark:text-white">{{ $line->quantity }}</span>
                                    <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                            aria-label="Increase quantity">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                {{ \App\Support\MoneyFormatter::format($line->line_total_amount) }}
                            </td>
                            <td class="py-4 pl-4 text-right">
                                <button wire:click="removeLine({{ $line->id }})"
                                        class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                        aria-label="Remove {{ $line->product_title }} from cart">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-4">
            @foreach($lines as $line)
                <div wire:key="mobile-line-{{ $line->id }}" class="flex gap-3 pb-4 border-b border-gray-200 dark:border-gray-800">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                        @if($line->variant?->product?->media?->first())
                            <img src="{{ $line->variant->product->media->sortBy('position')->first()->storage_key }}" alt="" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $line->product_title }}</p>
                        @if($line->variant_title)
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $line->variant_title }}</p>
                        @endif
                        <div class="flex items-center justify-between mt-2">
                            <div class="inline-flex items-center border border-gray-300 dark:border-gray-600 rounded">
                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})" class="w-7 h-7 flex items-center justify-center text-gray-500" aria-label="Decrease">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                </button>
                                <span class="w-8 text-center text-xs font-medium text-gray-900 dark:text-white">{{ $line->quantity }}</span>
                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" class="w-7 h-7 flex items-center justify-center text-gray-500" aria-label="Increase">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($line->line_total_amount) }}</span>
                        </div>
                        <button wire:click="removeLine({{ $line->id }})" class="mt-1 text-xs text-gray-400 hover:text-red-600 dark:hover:text-red-400">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Totals --}}
        <div class="mt-8 flex flex-col items-end">
            <div class="w-full md:w-80 space-y-3">
                {{-- Discount code --}}
                <div class="flex gap-2">
                    @if($cart?->discount_code)
                        <div class="flex items-center justify-between w-full px-3 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <span class="text-sm text-green-700 dark:text-green-300 font-medium">{{ $cart->discount_code }}</span>
                            <button wire:click="removeDiscount" class="text-green-600 hover:text-green-800 dark:hover:text-green-200 text-xs">Remove</button>
                        </div>
                    @else
                        <input type="text" wire:model="discountCode" placeholder="Discount code"
                               class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500">
                        <button wire:click="applyDiscount"
                                class="px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            Apply
                        </button>
                    @endif
                </div>
                @if($discountError)
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $discountError }}</p>
                @endif

                <div class="space-y-2 pt-3 border-t border-gray-200 dark:border-gray-800">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($subtotal) }}</span>
                    </div>
                    @if($discountAmount > 0)
                        <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                            <span>Discount</span>
                            <span>-{{ \App\Support\MoneyFormatter::format($discountAmount) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-200 dark:border-gray-800">
                        <span class="text-gray-900 dark:text-white">Estimated total</span>
                        <span class="text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($total) }}</span>
                    </div>
                </div>

                <p class="text-xs text-gray-400">Shipping and taxes calculated at checkout</p>

                <button wire:click="proceedToCheckout"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                    Proceed to Checkout
                </button>
                <a href="{{ route('storefront.collections.index') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    Continue shopping
                </a>
            </div>
        </div>
    @endif
</div>
