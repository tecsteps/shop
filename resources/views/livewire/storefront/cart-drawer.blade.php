<div>
    {{-- Cart Drawer --}}
    <div x-data="{ open: @entangle('open') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50"
         role="dialog"
         aria-modal="true"
         aria-label="Shopping cart"
         @keydown.escape.window="open = false; $wire.closeDrawer()">

        {{-- Backdrop --}}
        <div x-show="open"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false; $wire.closeDrawer()"
             class="fixed inset-0 bg-black/50"></div>

        {{-- Panel --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 w-full max-w-sm bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 px-4 py-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Your Cart</h2>
                <button @click="open = false; $wire.closeDrawer()"
                        class="rounded-md p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        aria-label="Close cart">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Content - stub for now --}}
            <div class="flex flex-1 flex-col items-center justify-center px-4 text-center">
                <svg class="h-16 w-16 text-zinc-200 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <p class="mt-4 text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
                <button @click="open = false; $wire.closeDrawer()"
                        class="mt-4 rounded-md border border-zinc-300 dark:border-zinc-600 px-6 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                    Continue shopping
                </button>
            </div>
        </div>
    </div>
</div>
