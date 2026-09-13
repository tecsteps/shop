@props(['lines', 'totals', 'currency', 'discountCode' => null, 'showDiscountInput' => false])

@php
    $money = fn (int $cents): string => \App\Support\Money::format($cents, (string) $currency);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg bg-zinc-100 p-6 dark:bg-zinc-900']) }} aria-live="polite">
    <h2 class="text-lg font-semibold">Order Summary</h2>

    <ul class="mt-4 flex flex-col gap-3">
        @foreach ($lines as $line)
            <li wire:key="summary-line-{{ $line['id'] ?? $loop->index }}" class="flex items-center gap-3">
                @if (($line['image_url'] ?? null))
                    <img src="{{ $line['image_url'] }}" alt="" class="h-12 w-12 shrink-0 rounded object-cover" loading="lazy" />
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ $line['title'] }}</p>
                    @if (($line['variant'] ?? '') !== '')
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant'] }}</p>
                    @endif
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Qty: {{ $line['quantity'] }}</p>
                </div>
                <p class="text-sm font-semibold">{{ $money((int) $line['total']) }}</p>
            </li>
        @endforeach
    </ul>

    @if ($showDiscountInput)
        <div class="mt-4">
            @if ($discountCode)
                <p class="flex items-center justify-between text-sm text-green-700 dark:text-green-400">
                    <span>{{ $discountCode }} applied</span>
                    <button type="button" wire:click="removeDiscount" class="underline">Remove</button>
                </p>
            @else
                <div class="flex gap-2">
                    <flux:input wire:model="discountCodeInput" placeholder="Discount code" class="text-sm" aria-label="Discount code" />
                    <flux:button wire:click="applyDiscount" variant="ghost" wire:loading.attr="disabled">Apply</flux:button>
                </div>
                @error('discountCodeInput') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if (session()->has('discount_error'))
                    <p class="mt-1 text-sm text-red-600">{{ session('discount_error') }}</p>
                @endif
            @endif
        </div>
    @elseif ($discountCode)
        <p class="mt-4 text-sm text-green-700 dark:text-green-400">{{ $discountCode }} applied</p>
    @endif

    <dl class="mt-4 flex flex-col gap-1 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700">
        <div class="flex justify-between">
            <dt>Subtotal</dt>
            <dd>{{ $money((int) ($totals['subtotal'] ?? 0)) }}</dd>
        </div>
        @if ((int) ($totals['discount'] ?? 0) > 0)
            <div class="flex justify-between text-green-700 dark:text-green-400">
                <dt>Discount</dt>
                <dd>-{{ $money((int) $totals['discount']) }}</dd>
            </div>
        @endif
        <div class="flex justify-between">
            <dt>Shipping</dt>
            <dd>{{ array_key_exists('shipping', $totals) ? $money((int) $totals['shipping']) : 'Calculated at next step' }}</dd>
        </div>
        @if (array_key_exists('tax', $totals))
            <div class="flex justify-between">
                <dt>Tax</dt>
                <dd>{{ $money((int) $totals['tax']) }}</dd>
            </div>
        @endif
        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-700">
            <dt>Total</dt>
            <dd>{{ $money((int) ($totals['total'] ?? 0)) }}</dd>
        </div>
    </dl>
</div>
