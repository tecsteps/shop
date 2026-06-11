@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    'wireModel' => null,
    'compact' => false,
    'label' => null,
])

@php
    $label ??= __('Quantity');
    $buttonSize = $compact ? 'size-8' : 'size-10';
    $inputSize = $compact ? 'h-8 w-10 text-xs' : 'h-10 w-14 text-sm';
    $alpineValue = $wireModel !== null
        ? "\$wire.entangle('".$wireModel."')"
        : (string) max((int) $value, (int) $min);
@endphp

<div
    {{ $attributes->class('inline-flex items-stretch overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700') }}
    x-data="{
        quantity: {{ $alpineValue }},
        min: {{ (int) $min }},
        max: {{ $max === null ? 'null' : (int) $max }},
        decrease() { this.quantity = Math.max(this.min, (parseInt(this.quantity) || this.min) - 1); },
        increase() {
            const next = (parseInt(this.quantity) || this.min) + 1;
            this.quantity = this.max === null ? next : Math.min(this.max, next);
        },
    }"
>
    <button
        type="button"
        x-on:click="decrease()"
        x-bind:disabled="(parseInt(quantity) || min) <= min"
        class="{{ $buttonSize }} flex items-center justify-center text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
        aria-label="{{ __('Decrease quantity') }}"
    >
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
    </button>
    <input
        type="number"
        x-model.number="quantity"
        min="{{ $min }}"
        @if ($max !== null) max="{{ $max }}" @endif
        class="{{ $inputSize }} border-x border-zinc-300 bg-transparent text-center font-medium text-zinc-900 [appearance:textfield] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        aria-label="{{ $label }}"
    />
    <button
        type="button"
        x-on:click="increase()"
        x-bind:disabled="max !== null && (parseInt(quantity) || min) >= max"
        class="{{ $buttonSize }} flex items-center justify-center text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
        aria-label="{{ __('Increase quantity') }}"
    >
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    </button>
</div>
