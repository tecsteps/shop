<div
    x-data="{ open: false }"
    x-on:open-cart-drawer.window="open = true"
    x-on:cart-updated.window="open = true"
    x-on:keydown.escape.window="open = false"
    class="relative"
>
    <button
        type="button"
        x-on:click="open = true"
        aria-label="Open cart"
        class="relative inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
        data-testid="cart-drawer-toggle"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
            <path d="M1 1.75A.75.75 0 0 1 1.75 1h1.628a1.75 1.75 0 0 1 1.734 1.51L5.18 3a65.25 65.25 0 0 1 13.36 1.412.75.75 0 0 1 .58.875 48.645 48.645 0 0 1-1.618 6.2.75.75 0 0 1-.712.513H6a2.503 2.503 0 0 0-2.292 1.5H17.25a.75.75 0 0 1 0 1.5H2.76a.75.75 0 0 1-.748-.807 4.002 4.002 0 0 1 2.716-3.486L3.626 2.716a.25.25 0 0 0-.248-.216H1.75A.75.75 0 0 1 1 1.75ZM6 17.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM15.5 17.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z" />
        </svg>
        <span>Cart</span>
        <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-zinc-900 px-1.5 text-[10px] font-semibold text-white dark:bg-white dark:text-zinc-900" data-testid="cart-drawer-count">{{ $count }}</span>
    </button>

    <div
        x-show="open"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 z-50 bg-black/40"
        x-on:click="open = false"
    ></div>

    <aside
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        x-cloak
        role="dialog"
        aria-label="Shopping cart"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-950"
    >
        <header class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Your cart</h2>
            <button
                type="button"
                x-on:click="open = false"
                aria-label="Close cart"
                class="rounded-full p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-5 py-4" data-testid="cart-drawer-body">
            @if ($cart === null || $cart->lines->isEmpty())
                <p class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">Your cart is empty.</p>
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($cart->lines as $line)
                        <li wire:key="drawer-line-{{ $line->id }}" class="flex gap-3 py-4">
                            <div class="h-16 w-16 flex-shrink-0 rounded-lg bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900"></div>
                            <div class="flex flex-1 flex-col gap-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $line->variant?->product?->title ?? 'Product' }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty {{ $line->quantity }}</p>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    <x-storefront.price :amount="$line->line_total_amount" :currency="$cart->currency" />
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <footer class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <div class="flex items-center justify-between pb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                <span>Subtotal</span>
                <span><x-storefront.price :amount="$subtotal" :currency="$cart?->currency ?? 'USD'" /></span>
            </div>
            <div class="flex flex-col gap-2">
                <a
                    href="{{ route('storefront.cart.show') }}"
                    class="inline-flex w-full items-center justify-center rounded-full border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                >
                    View cart
                </a>
                <a
                    href="{{ route('storefront.checkout.show') }}"
                    class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    Checkout
                </a>
            </div>
        </footer>
    </aside>
</div>
