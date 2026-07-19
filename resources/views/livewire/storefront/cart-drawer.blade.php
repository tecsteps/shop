<div class="flex h-full flex-col">
    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Your Cart @if ($cart !== null && $cart->itemCount() > 0) ({{ $cart->itemCount() }}) @endif
        </h2>
        <button type="button"
                @click="open = false"
                class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                aria-label="Close cart">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    @if ($cart === null || $cart->lines->isEmpty())
        {{-- Empty state (spec 04 §6.7) --}}
        <div class="flex flex-1 flex-col items-center justify-center gap-4 px-4 py-12 text-center">
            <svg class="size-16 text-gray-300 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Your cart is empty</p>
            <button type="button"
                    @click="open = false"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                Continue shopping
            </button>
        </div>
    @else
        {{-- Line items --}}
        <ul class="flex-1 divide-y divide-gray-200 overflow-y-auto px-4 dark:divide-gray-800" aria-live="polite">
            @foreach ($cart->lines as $line)
                <li wire:key="drawer-line-{{ $line->id }}" class="flex gap-4 py-4" wire:loading.class="opacity-50" wire:target="incrementLine({{ $line->id }}), decrementLine({{ $line->id }}), removeLine({{ $line->id }})">
                    @php $image = $line->variant?->product?->media->first(); @endphp
                    @if ($image !== null)
                        <img src="{{ $image->url() }}" alt="" class="size-16 shrink-0 rounded-md object-cover">
                    @else
                        <div class="flex size-16 shrink-0 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-800" aria-hidden="true">
                            <svg class="size-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25z" />
                            </svg>
                        </div>
                    @endif
                    <div class="flex flex-1 flex-col gap-1">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $line->variant?->product?->title }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $line->variant?->title() }}</p>
                        <div class="mt-auto flex items-center justify-between pt-1">
                            <div class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-700">
                                <button type="button" wire:click="decrementLine({{ $line->id }})"
                                        class="flex size-8 items-center justify-center text-gray-600 hover:text-gray-900 focus:outline-hidden dark:text-gray-300 dark:hover:text-white"
                                        aria-label="Decrease quantity of {{ $line->variant?->product?->title }}">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                </button>
                                <span class="w-8 text-center text-sm tabular-nums text-gray-900 dark:text-white">{{ $line->quantity }}</span>
                                <button type="button" wire:click="incrementLine({{ $line->id }})"
                                        class="flex size-8 items-center justify-center text-gray-600 hover:text-gray-900 focus:outline-hidden dark:text-gray-300 dark:hover:text-white"
                                        aria-label="Increase quantity of {{ $line->variant?->product?->title }}">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                </button>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ \App\Support\Money::format($line->line_total_amount, $cart->currency) }}</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" wire:click="removeLine({{ $line->id }})"
                                    class="rounded p-1 text-gray-400 hover:text-red-600 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:hover:text-red-400"
                                    aria-label="Remove {{ $line->variant?->product?->title }} from cart">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        {{-- Discount code (spec 04 §6.4) --}}
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
            @if ($discount !== null)
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">
                        {{ $discount['code'] }} ({{ $discount['label'] }})
                    </p>
                    <button type="button" wire:click="removeDiscount" class="text-xs font-medium text-gray-500 underline hover:text-gray-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-gray-200">
                        Remove
                    </button>
                </div>
            @else
                <form wire:submit="applyDiscount" class="flex gap-2">
                    <label for="drawer-discount-code" class="sr-only">Discount code</label>
                    <input id="drawer-discount-code" type="text" wire:model="discountCode" placeholder="Discount code"
                           class="min-w-0 flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <button type="submit"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                            wire:loading.attr="disabled" wire:target="applyDiscount">
                        <span wire:loading.remove wire:target="applyDiscount">Apply</span>
                        <span wire:loading wire:target="applyDiscount">Applying...</span>
                    </button>
                </form>
                @if ($discountError !== null)
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $discountError }}</p>
                @endif
            @endif
        </div>

        {{-- Totals (spec 04 §6.5) --}}
        <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
            <dl class="space-y-1">
                <div class="flex items-center justify-between text-sm">
                    <dt class="text-gray-600 dark:text-gray-400">Subtotal</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Support\Money::format($cart->subtotal(), $cart->currency) }}</dd>
                </div>
                @if ($discount !== null && $discount['amount'] > 0)
                    <div class="flex items-center justify-between text-sm text-green-600 dark:text-green-400">
                        <dt>Discount ({{ $discount['code'] }})</dt>
                        <dd>-{{ \App\Support\Money::format($discount['amount'], $cart->currency) }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold dark:border-gray-800">
                    <dt class="text-gray-900 dark:text-white">Estimated total</dt>
                    <dd class="text-gray-900 dark:text-white">{{ \App\Support\Money::format($cart->subtotal() - ($discount['amount'] ?? 0), $cart->currency) }}</dd>
                </div>
            </dl>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Shipping and taxes calculated at checkout</p>
            <button type="button" wire:click="checkout"
                    class="mt-4 w-full rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-950">
                Checkout
            </button>
            <div class="mt-2 text-center">
                <button type="button" @click="open = false" class="text-sm text-gray-500 hover:text-gray-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-gray-200">
                    Continue shopping
                </button>
            </div>
        </div>
    @endif
</div>
