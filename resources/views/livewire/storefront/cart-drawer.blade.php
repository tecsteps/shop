<div>
    {{-- Cart drawer (placeholder for Phase 4) --}}
    @if($isOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Shopping cart">
            {{-- Backdrop --}}
            <div wire:click="closeDrawer" class="fixed inset-0 bg-black/50 transition-opacity"></div>

            {{-- Panel --}}
            <div class="fixed inset-y-0 right-0 flex max-w-full">
                <div class="w-screen max-w-sm bg-white p-6 shadow-xl dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Your Cart</h2>
                        <button wire:click="closeDrawer"
                                class="rounded-md p-2 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                aria-label="Close cart">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-8 flex flex-1 flex-col items-center justify-center py-12">
                        <svg class="h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
                        <button wire:click="closeDrawer"
                                class="mt-4 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            Continue shopping
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
