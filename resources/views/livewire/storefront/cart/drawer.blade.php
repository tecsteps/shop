<div class="relative">
    @php
        $count = $cart ? (int) $cart->totalQuantity() : 0;
    @endphp

    <button type="button" wire:click="toggle"
        aria-haspopup="dialog"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        aria-controls="cart-drawer-panel"
        aria-label="Toggle cart"
        class="inline-flex items-center gap-2 rounded text-sm font-medium text-neutral-700 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-white">
        <span>Cart</span>
        <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-neutral-900 px-2 py-0.5 text-xs font-semibold text-white dark:bg-white dark:text-neutral-900">
            {{ $count }}
        </span>
        <span wire:loading wire:target="toggle,refreshDrawer" class="ml-1 text-xs text-neutral-500">Loading...</span>
    </button>

    @if ($open)
        <div role="presentation"
            wire:click="close"
            class="fixed inset-0 z-40 bg-neutral-900/40 backdrop-blur-sm"></div>

        <aside
            id="cart-drawer-panel"
            role="dialog"
            aria-modal="true"
            aria-label="Shopping cart"
            class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md flex-col bg-white shadow-xl dark:bg-neutral-950">
            <header class="flex items-center justify-between border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <h2 class="text-base font-semibold">Your cart</h2>
                <button type="button"
                    wire:click="close"
                    aria-label="Close cart"
                    class="rounded p-1 text-neutral-500 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:hover:text-white dark:focus-visible:ring-white">
                    <span aria-hidden="true">&times;</span>
                </button>
            </header>

            <div class="flex-1 overflow-y-auto px-5 py-4">
                @if ($cart === null || $lines->isEmpty())
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Your cart is empty.</p>
                @else
                    <ul class="flex flex-col gap-4">
                        @foreach ($lines as $line)
                            <li wire:key="drawer-line-{{ $line->id }}"
                                class="flex items-start justify-between gap-4 border-b border-neutral-100 pb-4 last:border-b-0 dark:border-neutral-800">
                                <div class="flex-1">
                                    <p class="text-sm font-medium">{{ $line->variant?->product?->title ?? 'Item' }}</p>
                                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                        Qty {{ $line->quantity }} &middot; SKU {{ $line->variant?->sku ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="text-sm font-semibold">
                                    {{ number_format($line->line_total_amount / 100, 2) }} {{ $cart->currency }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($cart !== null && ! $lines->isEmpty())
                <footer class="flex flex-col gap-3 border-t border-neutral-200 px-5 py-4 dark:border-neutral-800">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-neutral-600 dark:text-neutral-400">Subtotal</span>
                        <span class="font-semibold">
                            {{ number_format($cart->subtotal() / 100, 2) }} {{ $cart->currency }}
                        </span>
                    </div>
                    <a href="{{ url('/checkout') }}"
                        wire:loading.attr="aria-disabled"
                        class="inline-flex items-center justify-center rounded-full bg-neutral-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 dark:focus-visible:ring-white">
                        <span>Checkout</span>
                        <span wire:loading wire:target="refreshDrawer" class="ml-2 text-xs">...</span>
                    </a>
                    <a href="{{ url('/cart') }}"
                        class="text-center text-xs font-medium text-neutral-600 underline hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:text-neutral-400 dark:hover:text-white">
                        View full cart
                    </a>
                </footer>
            @endif
        </aside>
    @endif
</div>
