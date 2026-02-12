<div>
    {{-- Backdrop --}}
    @if($open)
        <div class="fixed inset-0 z-[70] bg-black/50 transition-opacity"
             wire:click="closeDrawer"
             x-data
             @keydown.escape.window="$wire.closeDrawer()">
        </div>
    @endif

    {{-- Drawer Panel --}}
    <div class="fixed inset-y-0 right-0 z-[80] w-full sm:w-96 bg-white dark:bg-gray-950 shadow-xl transform transition-transform duration-300 ease-in-out flex flex-col {{ $open ? 'translate-x-0' : 'translate-x-full' }}"
         role="dialog"
         aria-modal="true"
         aria-label="Shopping cart">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Your Cart ({{ $itemCount }})
            </h2>
            <button wire:click="closeDrawer"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    aria-label="Close cart">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if($lines->isEmpty())
            {{-- Empty State --}}
            <div class="flex-1 flex flex-col items-center justify-center px-4">
                <svg class="w-16 h-16 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Your cart is empty</p>
                <button wire:click="closeDrawer"
                        class="mt-4 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    Continue shopping
                </button>
            </div>
        @else
            {{-- Line Items --}}
            <div class="flex-1 overflow-y-auto px-4 py-4">
                <div class="space-y-4">
                    @foreach($lines as $line)
                        <div wire:key="drawer-line-{{ $line->id }}" class="flex gap-3 pb-4 border-b border-gray-100 dark:border-gray-800 last:border-0">
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
                                        <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})"
                                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                                aria-label="Decrease quantity">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                        <span class="w-8 text-center text-xs font-medium text-gray-900 dark:text-white">{{ $line->quantity }}</span>
                                        <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                                aria-label="Increase quantity">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($line->line_total_amount) }}</span>
                                </div>
                                <button wire:click="removeLine({{ $line->id }})"
                                        class="mt-1 text-xs text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                        aria-label="Remove {{ $line->product_title }} from cart">
                                    Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Totals + Actions --}}
            <div class="border-t border-gray-200 dark:border-gray-800 px-4 py-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ \App\Support\MoneyFormatter::format($subtotal) }}</span>
                </div>
                <p class="text-xs text-gray-400">Shipping and taxes calculated at checkout</p>

                <button wire:click="proceedToCheckout"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                    Checkout
                </button>
                <button wire:click="closeDrawer"
                        class="w-full text-center text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    Continue shopping
                </button>
            </div>
        @endif
    </div>
</div>
