<div
    x-data="{ open: $wire.entangle('open') }"
    x-on:keydown.escape.window="open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
>
    <div x-show="open" x-cloak class="relative z-50" role="dialog" aria-modal="true" aria-label="{{ __('Shopping cart') }}">
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition.opacity.duration.200ms
            x-on:click="open = false"
            class="fixed inset-0 bg-zinc-950/50"
            aria-hidden="true"
        ></div>

        {{-- Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-xl dark:bg-zinc-950"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                    {{ __('Your Cart') }} ({{ $itemCount }})
                </h2>
                <button
                    type="button"
                    x-on:click="open = false"
                    x-ref="closeButton"
                    class="rounded-lg p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    aria-label="{{ __('Close cart') }}"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if ($lines === [])
                {{-- Empty state --}}
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <svg class="size-16 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                    </svg>
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Your cart is empty') }}</p>
                    <button
                        type="button"
                        x-on:click="open = false"
                        class="mt-6 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        {{ __('Continue shopping') }}
                    </button>
                </div>
            @else
                {{-- Line items --}}
                <ul class="flex-1 divide-y divide-zinc-200 overflow-y-auto px-4 dark:divide-zinc-800" aria-live="polite">
                    @foreach ($lines as $line)
                        <li class="flex gap-3 py-4" wire:key="drawer-line-{{ $line['id'] }}" wire:loading.class="opacity-50" wire:target="updateQuantity, removeLine">
                            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-900">
                                @if ($line['image_url'] !== null)
                                    <img src="{{ $line['image_url'] }}" alt="" class="size-full object-cover" loading="lazy" />
                                @endif
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $line['title'] }}</p>
                                @if ($line['variant_label'] !== '')
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant_label'] }}</p>
                                @endif
                                <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                                    <div class="inline-flex items-stretch overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700">
                                        <button
                                            type="button"
                                            wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] - 1 }})"
                                            class="flex size-8 items-center justify-center text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            aria-label="{{ __('Decrease quantity') }}"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14" /></svg>
                                        </button>
                                        <span class="flex h-8 w-9 items-center justify-center border-x border-zinc-300 text-xs font-medium text-zinc-900 dark:border-zinc-700 dark:text-white">{{ $line['quantity'] }}</span>
                                        <button
                                            type="button"
                                            wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] + 1 }})"
                                            class="flex size-8 items-center justify-center text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            aria-label="{{ __('Increase quantity') }}"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                                        </button>
                                    </div>
                                    <x-storefront.price :amount="$line['line_total_amount']" :currency="$currency" class="text-sm" />
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button
                                        type="button"
                                        wire:click="removeLine({{ $line['id'] }})"
                                        class="rounded p-1 text-zinc-400 transition hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-500 dark:hover:text-red-400"
                                        aria-label="{{ __('Remove :item from cart', ['item' => $line['title']]) }}"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($cartError !== null)
                    <p class="px-4 py-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $cartError }}</p>
                @endif

                {{-- Discount code --}}
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    @if ($discount !== null)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-green-700 dark:text-green-400">
                                {{ $discount['code'] }}
                                @if ($discount['free_shipping'])
                                    ({{ __('Free shipping') }})
                                @else
                                    (-{{ \App\Support\Storefront\PriceFormatter::format($discount['amount'], $currency) }})
                                @endif
                            </span>
                            <button
                                type="button"
                                wire:click="removeDiscount"
                                class="text-xs font-medium text-zinc-500 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-400 dark:hover:text-white"
                            >
                                {{ __('Remove') }}
                            </button>
                        </div>
                    @else
                        <form wire:submit="applyDiscount" class="flex gap-2">
                            <label for="drawer-discount-code" class="sr-only">{{ __('Discount code') }}</label>
                            <input
                                id="drawer-discount-code"
                                type="text"
                                wire:model="discountCode"
                                placeholder="{{ __('Discount code') }}"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500"
                            />
                            <button
                                type="submit"
                                class="shrink-0 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <span wire:loading.remove wire:target="applyDiscount">{{ __('Apply') }}</span>
                                <span wire:loading wire:target="applyDiscount">{{ __('Applying...') }}</span>
                            </button>
                        </form>
                        @if ($discountError !== null)
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $discountError }}</p>
                        @endif
                    @endif
                </div>

                {{-- Totals + actions --}}
                <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-800">
                    <dl class="space-y-1.5 text-sm" aria-live="polite">
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Subtotal') }}</dt>
                            <dd><x-storefront.price :amount="$subtotalAmount" :currency="$currency" class="text-sm" /></dd>
                        </div>
                        @if ($discount !== null && $discount['amount'] > 0)
                            <div class="flex items-center justify-between text-green-700 dark:text-green-400">
                                <dt>{{ __('Discount') }} ({{ $discount['code'] }})</dt>
                                <dd class="font-medium">-{{ \App\Support\Storefront\PriceFormatter::format($discount['amount'], $currency) }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-zinc-200 pt-2 text-base dark:border-zinc-800">
                            <dt class="font-semibold text-zinc-900 dark:text-white">{{ __('Estimated total') }}</dt>
                            <dd><x-storefront.price :amount="$estimatedTotal" :currency="$currency" /></dd>
                        </div>
                    </dl>
                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Shipping and taxes calculated at checkout') }}</p>

                    <a
                        href="{{ route('storefront.checkout') }}"
                        class="mt-4 block w-full rounded-lg px-4 py-3 text-center text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                        style="background-color: var(--sf-primary, #2563eb);"
                    >
                        {{ __('Checkout') }}
                    </a>
                    <button
                        type="button"
                        x-on:click="open = false"
                        class="mt-3 block w-full text-center text-sm text-zinc-500 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-400 dark:hover:text-white"
                    >
                        {{ __('Continue shopping') }}
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
