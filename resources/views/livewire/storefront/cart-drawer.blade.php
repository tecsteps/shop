<div
    class="pointer-events-none fixed inset-0 z-50"
    wire:key="cart-drawer"
    x-data="{ opened: false }"
    x-effect="if ($wire.open) { opened = true; document.body.classList.add('overflow-hidden'); } else { opened = false; document.body.classList.remove('overflow-hidden'); }"
    @keydown.escape.window="opened = false; $wire.closeDrawer()"
>
    {{-- Backdrop --}}
    <div
        x-show="opened"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="pointer-events-auto absolute inset-0 bg-zinc-950/50 backdrop-blur-sm"
        @click="opened = false; $wire.closeDrawer()"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        x-show="opened"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="pointer-events-auto absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl dark:bg-zinc-950 sm:w-[384px]"
        role="dialog"
        aria-modal="true"
        aria-label="Shopping cart"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                Your Cart
                <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ $this->lines->count() }})</span>
            </h2>
            <button
                type="button"
                x-ref="closeBtn"
                @click="opened = false; $wire.closeDrawer()"
                aria-label="Close cart"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        @if ($this->lines->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                <svg class="size-14 text-zinc-300 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="8" cy="21" r="1" />
                    <circle cx="19" cy="21" r="1" />
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                </svg>
                <p class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">Your cart is empty</p>
                <button
                    type="button"
                    @click="opened = false; $wire.closeDrawer()"
                    class="mt-6 rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                >
                    Continue shopping
                </button>
            </div>
        @else
            {{-- Line items --}}
            <ul class="flex-1 space-y-5 overflow-y-auto px-5 py-4" aria-live="polite">
                @foreach ($this->lines as $line)
                    @php
                        $product = $line->variant?->product;
                        $image = $product?->media->where('type', 'image')->first();
                        $options = $line->variant?->optionValues->sortBy(fn ($value) => $value->option?->position ?? 0)->pluck('value')->join(' / ');
                    @endphp
                    <li wire:key="drawer-line-{{ $line->id }}" class="flex gap-4 border-b border-zinc-100 pb-5 last:border-0 dark:border-zinc-800" wire:loading.class="opacity-60">
                        <span class="shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @if ($image)
                                <img src="{{ Storage::url($image->storage_key) }}" alt="" class="size-16 object-cover" />
                            @else
                                <span class="flex size-16 items-center justify-center text-zinc-300 dark:text-zinc-600">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" /></svg>
                                </span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('storefront.product', ['handle' => $product?->handle ?? '']) }}" class="block truncate text-sm font-semibold text-zinc-900 transition hover:underline dark:text-white">
                                    {{ $product?->title }}
                                </a>
                                <x-storefront-price :amount="$line->line_total_amount" :currency="$this->currency" class="text-sm" />
                            </div>

                            @if ($options !== '')
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $options }}</p>
                            @endif

                            <div class="mt-2 flex items-center justify-between">
                                <x-storefront-quantity-selector
                                    :value="$line->quantity"
                                    :min="1"
                                    :compact="true"
                                    decrement="decrementLine({{ $line->id }})"
                                    increment="incrementLine({{ $line->id }})"
                                />
                                <button
                                    type="button"
                                    wire:click="removeLine({{ $line->id }})"
                                    aria-label="Remove {{ $product?->title ?? 'item' }} from cart"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Discount + totals --}}
            <div class="border-t border-zinc-200 px-5 pb-5 pt-4 dark:border-zinc-800">
                @if ($this->cartDiscount)
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-emerald-50 px-3 py-2.5 text-sm text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <span class="font-medium">
                            {{ $this->cartDiscount['code'] }}
                            <span class="text-xs">{{ $this->cartDiscount['label'] }}</span>
                        </span>
                        <button type="button" wire:click="removeCartDiscount" class="text-xs font-medium underline underline-offset-2 hover:no-underline">
                            Remove
                        </button>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            wire:model="discountCode"
                            placeholder="Discount code"
                            aria-label="Discount code"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                        />
                        <button
                            type="button"
                            wire:click="applyCartDiscount"
                            wire:loading.attr="disabled"
                            class="shrink-0 rounded-lg border border-zinc-300 px-3.5 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            <span wire:loading.remove wire:target="applyCartDiscount">Apply</span>
                            <span wire:loading wire:target="applyCartDiscount">Applying...</span>
                        </button>
                    </div>
                    @if ($this->discountError)
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ $this->discountError }}</p>
                    @endif
                @endif

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">
                            <x-storefront-price :amount="$this->subtotal" :currency="$this->currency" />
                        </dd>
                    </div>

                    @if ($this->cartDiscount)
                        <div class="flex items-center justify-between">
                            <dt class="text-emerald-600 dark:text-emerald-400">Discount ({{ $this->cartDiscount['code'] }})</dt>
                            <dd class="font-medium text-emerald-600 dark:text-emerald-400">
                                -<x-storefront-price :amount="$this->cartDiscount['amount']" :currency="$this->currency" />
                            </dd>
                        </div>
                    @endif

                    <div class="flex items-center justify-between border-t border-zinc-200 pt-2.5 dark:border-zinc-800">
                        <dt class="text-base font-semibold text-zinc-900 dark:text-white">Estimated total</dt>
                        <dd class="text-base font-semibold text-zinc-900 dark:text-white">
                            <x-storefront-price :amount="$this->total" :currency="$this->currency" />
                        </dd>
                    </div>
                </dl>

                <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
                    Shipping and taxes calculated at checkout.
                </p>

                <button
                    type="button"
                    wire:click="checkout"
                    wire:loading.attr="disabled"
                    class="mt-4 w-full rounded-lg bg-blue-600 px-6 py-3.5 text-base font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-500 dark:hover:bg-blue-400"
                >
                    Checkout
                </button>

                <button
                    type="button"
                    @click="opened = false; $wire.closeDrawer()"
                    class="mt-3 block w-full text-center text-sm text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    Continue shopping
                </button>
            </div>
        @endif
    </div>
</div>
