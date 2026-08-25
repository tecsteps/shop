@props([
    'checkout' => null,
    'showDiscountInput' => true,
])

@php
    if (! $checkout) {
        return;
    }

    $cart = $checkout->cart()->with([
        'lines.variant.product.media',
        'lines.variant.optionValues.option',
    ])->first();

    $totals = $checkout->totals_json ?? [];
    $currency = $totals['currency'] ?? $cart?->currency ?? ($currentStore?->default_currency ?? 'EUR');
    $subtotal = (int) ($totals['subtotal'] ?? 0);
    $discount = (int) ($totals['discount'] ?? 0);
    $shipping = (int) ($totals['shipping'] ?? 0);
    $taxTotal = (int) ($totals['tax_total'] ?? 0);
    $total = (int) ($totals['total'] ?? 0);
    $lineCount = $cart?->lines->sum('quantity') ?? 0;
@endphp

<div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 sm:p-6 dark:border-zinc-800 dark:bg-zinc-900">
    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Order summary</h2>

    <ul class="mt-4 max-h-64 space-y-4 overflow-y-auto pr-1" aria-label="Items in your order">
        @forelse ($cart?->lines ?? [] as $line)
            <li class="flex items-start gap-3">
                <span class="relative shrink-0 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800">
                    @php
                        $image = $line->variant?->product?->media->where('type', 'image')->first();
                    @endphp
                    @if ($image)
                        <img src="{{ Storage::url($image->storage_key) }}" alt="" class="size-12 object-cover" />
                    @else
                        <span class="flex size-12 items-center justify-center text-zinc-400 dark:text-zinc-500">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                <path d="M3 6h18" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </svg>
                        </span>
                    @endif
                    @if ($line->quantity > 1)
                        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-zinc-900 px-1 text-xs font-medium text-white dark:bg-white dark:text-zinc-900">
                            {{ $line->quantity }}
                        </span>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">
                        {{ $line->variant?->product?->title }}
                    </span>
                    @php
                        $options = $line->variant?->optionValues->sortBy(fn ($value) => $value->option?->position ?? 0)->pluck('value')->join(' / ');
                    @endphp
                    @if ($options !== '')
                        <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $options }}</span>
                    @endif
                </span>

                <x-storefront-price :amount="$line->line_total_amount" :currency="$currency" class="text-sm" />
            </li>
        @empty
            <li class="text-sm text-zinc-500 dark:text-zinc-400">Your cart is empty.</li>
        @endforelse
    </ul>

    @if ($showDiscountInput)
        <div class="mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-800">
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
                    wire:click="applyCheckoutDiscount"
                    wire:loading.attr="disabled"
                    class="shrink-0 rounded-lg border border-zinc-300 px-3.5 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    <span wire:loading.remove wire:target="applyCheckoutDiscount">Apply</span>
                    <span wire:loading wire:target="applyCheckoutDiscount">Applying...</span>
                </button>
            </div>
            @if ($checkout->discount_code && $discount > 0)
                <div class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    <span class="font-medium">{{ $checkout->discount_code }}</span>
                    <button type="button" wire:click="removeCheckoutDiscount" class="text-xs font-medium underline underline-offset-2 hover:no-underline">
                        Remove
                    </button>
                </div>
            @endif
            @if ($discountError)
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $discountError }}</p>
            @endif
        </div>
    @endif

    <dl class="mt-5 space-y-2.5 border-t border-zinc-200 pt-5 text-sm dark:border-zinc-800" aria-live="polite">
        <div class="flex items-center justify-between">
            <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">
                <x-storefront-price :amount="$subtotal" :currency="$currency" />
            </dd>
        </div>

        @if ($discount > 0)
            <div class="flex items-center justify-between">
                <dt class="text-emerald-600 dark:text-emerald-400">Discount</dt>
                <dd class="font-medium text-emerald-600 dark:text-emerald-400">
                    -<x-storefront-price :amount="$discount" :currency="$currency" />
                </dd>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt>
            <dd class="font-medium text-zinc-900 dark:text-white">
                @if ($checkout->shipping_method_id)
                    <x-storefront-price :amount="$shipping" :currency="$currency" />
                @else
                    <span class="text-zinc-400 dark:text-zinc-500">Calculated at next step</span>
                @endif
            </dd>
        </div>

        @if ($taxTotal > 0)
            <div class="flex items-center justify-between">
                <dt class="text-zinc-600 dark:text-zinc-400">Tax</dt>
                <dd class="font-medium text-zinc-900 dark:text-white">
                    <x-storefront-price :amount="$taxTotal" :currency="$currency" />
                </dd>
            </div>
        @endif

        <div class="flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-800">
            <dt class="text-base font-semibold text-zinc-900 dark:text-white">Total</dt>
            <dd class="text-base font-semibold text-zinc-900 dark:text-white">
                <x-storefront-price :amount="$total" :currency="$currency" />
            </dd>
        </div>
    </dl>
</div>
