@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    'wireModel',
    'compact' => false,
    'disabled' => false,
])

@php
    $min = (int) $min;
    $max = filled($max) ? max($min, (int) $max) : null;
    $value = max($min, (int) $value);
    $value = $max !== null ? min($value, $max) : $value;
    $inputId = $attributes->get('id', 'quantity-'.str_replace(['.', '[', ']'], '-', (string) $wireModel));
    $controlSize = $compact ? 'size-8' : 'size-10';
    $inputSize = $compact ? 'h-8 w-10 text-sm' : 'h-10 w-14';
@endphp

<div
    x-data="{
        quantity: @js($value),
        minimum: @js($min),
        maximum: @js($max),
        commit() {
            let next = Number.parseInt(this.quantity, 10);
            next = Number.isFinite(next) ? next : this.minimum;
            this.quantity = Math.max(this.minimum, this.maximum === null ? next : Math.min(this.maximum, next));
            this.$nextTick(() => this.$refs.input.dispatchEvent(new Event('input', { bubbles: true })));
        },
    }"
    {{ $attributes->except(['id'])->class('inline-flex items-center overflow-hidden rounded-lg border border-zinc-300 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900') }}
>
    <button
        type="button"
        class="{{ $controlSize }} inline-flex shrink-0 items-center justify-center text-zinc-700 transition hover:bg-zinc-100 focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[var(--storefront-primary)] disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-200 dark:hover:bg-zinc-800"
        aria-label="{{ __('Decrease quantity') }}"
        x-bind:disabled="quantity <= minimum"
        x-on:click="quantity = Math.max(minimum, quantity - 1); commit()"
        @disabled($disabled || $value <= $min)
    >
        <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
            <path d="M4 10h12" stroke-linecap="round" />
        </svg>
    </button>

    <label for="{{ $inputId }}" class="sr-only">{{ __('Quantity') }}</label>
    <input
        id="{{ $inputId }}"
        x-ref="input"
        x-model.number="quantity"
        x-on:change="commit()"
        x-on:blur="commit()"
        wire:model.live="{{ $wireModel }}"
        type="number"
        inputmode="numeric"
        min="{{ $min }}"
        @if ($max !== null) max="{{ $max }}" @endif
        class="{{ $inputSize }} appearance-none border-x border-y-0 border-zinc-300 bg-transparent p-0 text-center font-medium tabular-nums text-zinc-950 focus:z-10 focus:border-[var(--storefront-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--storefront-primary)]/25 dark:border-zinc-700 dark:text-white"
        @disabled($disabled)
    >

    <button
        type="button"
        class="{{ $controlSize }} inline-flex shrink-0 items-center justify-center text-zinc-700 transition hover:bg-zinc-100 focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[var(--storefront-primary)] disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-200 dark:hover:bg-zinc-800"
        aria-label="{{ __('Increase quantity') }}"
        x-bind:disabled="maximum !== null && quantity >= maximum"
        x-on:click="quantity = maximum === null ? quantity + 1 : Math.min(maximum, quantity + 1); commit()"
        @disabled($disabled || ($max !== null && $value >= $max))
    >
        <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
            <path d="M10 4v12M4 10h12" stroke-linecap="round" />
        </svg>
    </button>
</div>
