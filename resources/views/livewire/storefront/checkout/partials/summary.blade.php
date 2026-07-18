<div class="rounded-xl bg-zinc-50 p-6 dark:bg-zinc-900">
    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Order Summary</h2>

    <ul class="mt-4 space-y-3">
        @foreach ($checkout->cart->lines as $line)
            <li class="flex items-center gap-3" wire:key="checkout-summary-line-{{ $line->id }}">
                <div class="relative size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800">
                    @php $thumb = $line->variant->product->media->sortBy('position')->first(); @endphp
                    @if ($thumb)
                        <img src="{{ $thumb->url ?? Storage::url($thumb->storage_key) }}" alt="" class="size-full object-cover" />
                    @endif
                    <span class="absolute -top-1.5 -right-1.5 flex size-5 items-center justify-center rounded-full bg-zinc-700 text-[10px] font-semibold text-white dark:bg-zinc-300 dark:text-zinc-900">
                        {{ $line->quantity }}
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $line->variant->product->title }}</p>
                    @if ($line->variant->optionValues->isNotEmpty())
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p>
                    @endif
                </div>
                <x-storefront.price :amount="$line->line_total_amount" :currency="$currency" />
            </li>
        @endforeach
    </ul>

    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
        @if ($checkout->discount_code)
            <div class="flex items-center justify-between rounded-lg bg-green-50 px-3 py-2 text-sm dark:bg-green-950">
                <span class="font-medium text-green-700 dark:text-green-400">{{ $checkout->discount_code }} applied</span>
                <button type="button" wire:click="removeDiscount" class="text-green-700 underline hover:text-green-900 dark:text-green-400">Remove</button>
            </div>
        @else
            <form wire:submit.prevent="applyDiscount" class="flex gap-2">
                <flux:input wire:model="discountCode" placeholder="Discount code" size="sm" class="flex-1" aria-label="Discount code" />
                <flux:button type="submit" size="sm" variant="filled">Apply</flux:button>
            </form>
            @if ($discountError)
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $discountError }}</p>
            @endif
        @endif
    </div>

    <dl class="mt-4 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800" aria-live="polite">
        <div class="flex justify-between">
            <dt class="text-zinc-500 dark:text-zinc-400">Subtotal</dt>
            <dd class="text-zinc-900 dark:text-white"><x-storefront.price :amount="$totals['subtotal']" :currency="$currency" /></dd>
        </div>
        @if ($totals['discount'] > 0)
            <div class="flex justify-between text-green-600 dark:text-green-400">
                <dt>Discount</dt>
                <dd>-{{ \App\Support\Money::format($totals['discount'], $currency) }}</dd>
            </div>
        @endif
        <div class="flex justify-between">
            <dt class="text-zinc-500 dark:text-zinc-400">Shipping</dt>
            <dd class="text-zinc-900 dark:text-white">
                {{ $this->step < 2 ? 'Calculated at next step' : \App\Support\Money::format($totals['shipping'], $currency) }}
            </dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-zinc-500 dark:text-zinc-400">Tax</dt>
            <dd class="text-zinc-900 dark:text-white">{{ \App\Support\Money::format($totals['tax_total'], $currency) }}</dd>
        </div>
        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold text-zinc-900 dark:border-zinc-800 dark:text-white">
            <dt>Total</dt>
            <dd><x-storefront.price :amount="$totals['total']" :currency="$currency" /></dd>
        </div>
    </dl>
</div>
