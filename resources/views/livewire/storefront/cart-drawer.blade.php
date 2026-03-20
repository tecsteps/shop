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

        {{-- Placeholder --}}
        <div class="mt-8 text-center">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Your cart is empty.</p>
            <a href="/collections" @click="show = false" class="mt-4 inline-block text-sm font-medium text-zinc-900 underline dark:text-white">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
