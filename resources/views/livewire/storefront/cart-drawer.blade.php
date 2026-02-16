<div>
    @if($open)
    <div
        x-data="{ show: true }"
        x-show="show"
        @keydown.escape.window="$wire.closeDrawer()"
        class="fixed inset-0 z-50"
        role="dialog"
        aria-modal="true"
        aria-label="Shopping cart"
    >
        {{-- Backdrop --}}
        <div x-show="show" x-transition.opacity wire:click="closeDrawer" class="fixed inset-0 bg-black/50"></div>

        {{-- Panel --}}
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-xl dark:bg-gray-900"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Cart ({{ $cart?->lines->count() ?? 0 }})</h2>
                <button wire:click="closeDrawer" class="p-2 text-gray-500 hover:text-gray-900 dark:hover:text-white" aria-label="Close cart">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if ($cart && $cart->lines->isNotEmpty())
                {{-- Cart Lines --}}
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    <div class="space-y-4">
                        @foreach ($cart->lines as $line)
                            <div class="flex gap-3">
                                <div class="h-16 w-16 shrink-0 rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $line->variant->product->title ?? 'Product' }}</h3>
                                    <p class="text-sm text-gray-500">${{ number_format($line->unit_price / 100, 2) }}</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})" class="rounded border px-1.5 py-0.5 text-xs dark:border-gray-700">-</button>
                                        <span class="text-xs text-gray-900 dark:text-white">{{ $line->quantity }}</span>
                                        <button wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" class="rounded border px-1.5 py-0.5 text-xs dark:border-gray-700">+</button>
                                        <button wire:click="removeLine({{ $line->id }})" class="ml-auto text-xs text-gray-400 hover:text-red-500">Remove</button>
                                    </div>
                                </div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($line->total / 100, 2) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
                    <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white">
                        <span>Subtotal</span>
                        <span>${{ number_format($cart->lines->sum('total') / 100, 2) }}</span>
                    </div>
                    <a href="{{ route('storefront.checkout') }}" class="mt-4 block w-full rounded-lg bg-blue-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
                        Checkout
                    </a>
                    <a href="{{ route('storefront.cart') }}" class="mt-2 block text-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        View cart
                    </a>
                </div>
            @else
                {{-- Empty state --}}
                <div class="flex h-full flex-col items-center justify-center pb-24">
                    <svg class="h-16 w-16 text-gray-300 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Your cart is empty</p>
                    <button wire:click="closeDrawer" class="mt-4 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        Continue shopping
                    </button>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
