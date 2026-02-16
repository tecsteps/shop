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
            class="fixed inset-y-0 right-0 w-full max-w-sm bg-white shadow-xl dark:bg-gray-900"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Cart (0)</h2>
                <button wire:click="closeDrawer" class="p-2 text-gray-500 hover:text-gray-900 dark:hover:text-white" aria-label="Close cart">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Empty state --}}
            <div class="flex h-full flex-col items-center justify-center pb-24">
                <svg class="h-16 w-16 text-gray-300 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Your cart is empty</p>
                <button wire:click="closeDrawer" class="mt-4 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
                    Continue shopping
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
