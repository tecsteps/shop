<div>
    {{-- Cart Drawer Backdrop & Panel --}}
    <div x-data="{ show: @entangle('open') }" x-cloak>
        {{-- Backdrop --}}
        <div x-show="show"
             x-transition:enter="transition-opacity duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="show = false"
             class="fixed inset-0 z-50 bg-black/50"></div>

        {{-- Drawer --}}
        <div x-show="show"
             x-transition:enter="transition-transform duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-xl dark:bg-gray-950"
             role="dialog"
             aria-label="Shopping cart">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cart ({{ $itemCount }})</h2>
                <button wire:click="closeDrawer"
                        class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        aria-label="Close cart">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Lines --}}
            <div class="flex-1 overflow-y-auto px-4 py-4">
                @if(count($lines) === 0)
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Your cart is empty.</p>
                @else
                    <div class="space-y-4">
                        @foreach($lines as $line)
                            <div wire:key="cart-line-{{ $line['id'] }}" class="flex gap-4 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $line['product_title'] }}</h3>
                                    @if($line['variant_title'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $line['variant_title'] }}</p>
                                    @endif
                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ number_format($line['unit_price_amount'] / 100, 2) }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex items-center gap-1">
                                        <button wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] - 1 }})"
                                                class="rounded border border-gray-300 px-2 py-0.5 text-sm hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">-</button>
                                        <span class="min-w-[2rem] text-center text-sm">{{ $line['quantity'] }}</span>
                                        <button wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] + 1 }})"
                                                class="rounded border border-gray-300 px-2 py-0.5 text-sm hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">+</button>
                                    </div>
                                    <button wire:click="removeLine({{ $line['id'] }})"
                                            class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @error('cart')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Footer --}}
            @if(count($lines) > 0)
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Subtotal</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($subtotal / 100, 2) }}</span>
                    </div>
                    <a href="{{ route('storefront.cart.show') }}"
                       class="block w-full rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        View Cart
                    </a>
                    <a href="{{ route('storefront.cart.show') }}"
                       class="mt-2 block w-full rounded-md bg-gray-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Checkout
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
