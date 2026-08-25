<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
            Your Cart
        </h1>

        @if (session('storefront_notice'))
            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-950/40 dark:text-amber-300" role="status">
                {{ session('storefront_notice') }}
            </p>
        @endif

        @if ($this->lines->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-col items-center py-24 text-center">
                <svg class="size-16 text-zinc-300 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="8" cy="21" r="1" />
                    <circle cx="19" cy="21" r="1" />
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                </svg>
                <p class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">Your cart is empty</p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Looks like you have not added anything yet.</p>
                <a
                    href="{{ route('storefront.collections.index') }}"
                    class="mt-6 rounded-lg border border-zinc-300 px-6 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                >
                    Continue shopping
                </a>
            </div>
        @else
            <div class="mt-8 lg:grid lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start lg:gap-10">
                {{-- Line items --}}
                <div>
                    {{-- Desktop table --}}
                    <div class="hidden overflow-hidden rounded-2xl border border-zinc-200 lg:block dark:border-zinc-800">
                        <table class="w-full text-left">
                            <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Product</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Price</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Quantity</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total</th>
                                    <th scope="col" class="px-5 py-3.5"><span class="sr-only">Remove</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                @foreach ($this->lines as $line)
                                    @php
                                        $product = $line->variant?->product;
                                        $image = $product?->media->where('type', 'image')->first();
                                        $options = $line->variant?->optionValues->sortBy(fn ($value) => $value->option?->position ?? 0)->pluck('value')->join(' / ');
                                    @endphp
                                    <tr wire:key="line-{{ $line->id }}" class="transition" wire:loading.class="opacity-60">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-4">
                                                <span class="shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                                    @if ($image)
                                                        <img src="{{ Storage::url($image->storage_key) }}" alt="" class="size-16 object-cover" />
                                                    @else
                                                        <span class="flex size-16 items-center justify-center text-zinc-300 dark:text-zinc-600">
                                                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" /></svg>
                                                        </span>
                                                    @endif
                                                </span>
                                                <div class="min-w-0">
                                                    <a href="{{ route('storefront.product', ['handle' => $product?->handle ?? '']) }}" class="block truncate text-sm font-semibold text-zinc-900 transition hover:underline dark:text-white">
                                                        {{ $product?->title }}
                                                    </a>
                                                    @if ($options !== '')
                                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $options }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <x-storefront-price :amount="$line->unit_price_amount" :currency="$this->currency" class="text-sm" />
                                        </td>
                                        <td class="px-5 py-4">
                                            <x-storefront-quantity-selector
                                                :value="$line->quantity"
                                                :min="1"
                                                :compact="true"
                                                decrement="decrementLine({{ $line->id }})"
                                                increment="incrementLine({{ $line->id }})"
                                            />
                                        </td>
                                        <td class="px-5 py-4">
                                            <x-storefront-price :amount="$line->line_total_amount" :currency="$this->currency" class="text-sm" />
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <button
                                                type="button"
                                                wire:click="removeLine({{ $line->id }})"
                                                aria-label="Remove {{ $product?->title ?? 'item' }} from cart"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                            >
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile cards --}}
                    <ul class="space-y-4 lg:hidden">
                        @foreach ($this->lines as $line)
                            @php
                                $product = $line->variant?->product;
                                $image = $product?->media->where('type', 'image')->first();
                                $options = $line->variant?->optionValues->sortBy(fn ($value) => $value->option?->position ?? 0)->pluck('value')->join(' / ');
                            @endphp
                            <li wire:key="m-line-{{ $line->id }}" class="rounded-2xl border border-zinc-200 p-4 transition dark:border-zinc-800" wire:loading.class="opacity-60">
                                <div class="flex items-start gap-4">
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
                                        <a href="{{ route('storefront.product', ['handle' => $product?->handle ?? '']) }}" class="block truncate text-sm font-semibold text-zinc-900 transition hover:underline dark:text-white">
                                            {{ $product?->title }}
                                        </a>
                                        @if ($options !== '')
                                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $options }}</p>
                                        @endif
                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <x-storefront-quantity-selector
                                                :value="$line->quantity"
                                                :min="1"
                                                :compact="true"
                                                decrement="decrementLine({{ $line->id }})"
                                                increment="incrementLine({{ $line->id }})"
                                            />
                                            <x-storefront-price :amount="$line->line_total_amount" :currency="$this->currency" class="text-sm" />
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="removeLine({{ $line->id }})"
                                            class="mt-3 text-xs font-medium text-zinc-400 underline underline-offset-2 transition hover:text-red-600 dark:hover:text-red-400"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Totals --}}
                <aside class="mt-8 rounded-2xl border border-zinc-200 bg-zinc-50 p-6 lg:sticky lg:top-24 lg:mt-0 dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Summary</h2>

                    {{-- Discount input --}}
                    <div class="mt-5">
                        @if ($this->cartDiscount)
                            <div class="flex items-center justify-between gap-2 rounded-lg bg-emerald-50 px-3 py-2.5 text-sm text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <span class="font-medium">{{ $this->cartDiscount['code'] }} <span class="text-xs">{{ $this->cartDiscount['label'] }}</span></span>
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
                    </div>

                    <dl class="mt-5 space-y-2.5 border-t border-zinc-200 pt-5 text-sm dark:border-zinc-800" aria-live="polite">
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

                        <div class="flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-800">
                            <dt class="text-base font-semibold text-zinc-900 dark:text-white">Estimated total</dt>
                            <dd class="text-base font-semibold text-zinc-900 dark:text-white">
                                <x-storefront-price :amount="$this->total" :currency="$this->currency" />
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-3 text-xs text-zinc-400 dark:text-zinc-500">
                        Shipping and taxes calculated at checkout.
                    </p>

                    <button
                        type="button"
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        class="mt-5 w-full rounded-lg bg-blue-600 px-6 py-3.5 text-base font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-500 dark:hover:bg-blue-400"
                    >
                        Checkout
                    </button>

                    <a
                        href="{{ route('storefront.collections.index') }}"
                        class="mt-3 block text-center text-sm text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                    >
                        Continue shopping
                    </a>
                </aside>
            </div>
        @endif
    </div>
</div>
