<div x-data="{ show: @entangle('open') }" @cart-updated.window="show = true" x-cloak>
    {{-- Overlay --}}
    <div x-show="show"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="show = false"
         class="fixed inset-0 z-40 bg-black/50"></div>

    {{-- Drawer --}}
    <div x-show="show"
         x-transition:enter="transition-transform duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-50 w-96 overflow-y-auto bg-white p-6 shadow-xl dark:bg-zinc-800">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Cart</h2>
            <button @click="show = false" wire:click="close" class="rounded-md p-1 text-zinc-500 hover:text-zinc-900 dark:hover:text-white" aria-label="Close cart">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if($lines->isEmpty())
            <div class="mt-8 text-center">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Your cart is empty.</p>
                <a href="/collections" @click="show = false" class="mt-4 inline-block text-sm font-medium text-zinc-900 underline dark:text-white">
                    Continue Shopping
                </a>
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach($lines as $line)
                    <div class="flex items-start gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-700" wire:key="drawer-line-{{ $line->id }}">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $line->variant->product->title }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">${{ number_format($line->unit_price_amount / 100, 2) }}</p>
                            <div class="mt-2 flex items-center gap-2">
                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})" class="rounded border border-zinc-300 px-2 py-0.5 text-xs dark:border-zinc-600">-</button>
                                <span class="text-sm text-zinc-900 dark:text-white">{{ $line->quantity }}</span>
                                <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" class="rounded border border-zinc-300 px-2 py-0.5 text-xs dark:border-zinc-600">+</button>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">${{ number_format($line->line_total_amount / 100, 2) }}</p>
                            <button wire:click="removeLine({{ $line->id }})" class="mt-1 text-xs text-red-500 hover:text-red-700">Remove</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <div class="flex justify-between text-sm font-medium text-zinc-900 dark:text-white">
                    <span>Subtotal</span>
                    <span>${{ number_format($subtotal / 100, 2) }}</span>
                </div>
                <a href="{{ route('storefront.cart') }}" @click="show = false" class="mt-4 block w-full rounded-md bg-zinc-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    View Cart
                </a>
            </div>
        @endif
    </div>
</div>
