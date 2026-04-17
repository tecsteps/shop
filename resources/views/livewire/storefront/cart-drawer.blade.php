<div>
    {{-- Cart drawer backdrop --}}
    <div x-data="{ open: @entangle('open') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50">
        {{-- Overlay --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 bg-black/50"></div>

        {{-- Drawer --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             x-trap.noscroll="open"
             class="fixed inset-y-0 right-0 w-full max-w-sm bg-white shadow-xl dark:bg-gray-900 sm:w-96"
             role="dialog"
             aria-modal="true"
             aria-label="Shopping cart">
            <div class="flex h-full flex-col">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cart</h2>
                    <button @click="open = false"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            aria-label="Close cart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Cart items --}}
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    {{-- Cart items will be populated once Phase 4 is complete --}}
                    <div class="flex h-full flex-col items-center justify-center text-center">
                        <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Your cart is empty</p>
                        <a href="{{ route('storefront.home') }}"
                           @click="open = false"
                           class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            Continue shopping
                        </a>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                    <a href="{{ route('storefront.cart') }}"
                       class="block w-full rounded-md bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                        View Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
