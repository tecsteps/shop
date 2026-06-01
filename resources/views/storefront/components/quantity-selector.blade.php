@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    // Livewire model binding string, e.g. "quantity".
    'wireModel',
    // Compact variant for the cart drawer / line items.
    'compact' => false,
])

@php
    $buttonSize = $compact ? 'h-8 w-8' : 'h-10 w-10';
    $inputSize = $compact ? 'h-8 w-10 text-sm' : 'h-10 w-14';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-stretch rounded-lg border border-zinc-300 dark:border-zinc-700']) }}
     x-data="{
        value: @js((int) $value),
        min: @js((int) $min),
        max: @js($max === null ? null : (int) $max),
        decrement() { if (this.value > this.min) { this.value--; this.sync(); } },
        increment() { if (this.max === null || this.value < this.max) { this.value++; this.sync(); } },
        sync() { $wire?.set(@js($wireModel), this.value); },
     }">
    <button type="button"
            x-on:click="decrement()"
            x-bind:disabled="value <= min"
            class="flex {{ $buttonSize }} items-center justify-center text-zinc-600 transition hover:text-zinc-900 disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-300 dark:hover:text-white"
            aria-label="Decrease quantity">
        <span aria-hidden="true">&minus;</span>
    </button>

    <input type="number"
           x-model.number="value"
           x-on:change="sync()"
           wire:model="{{ $wireModel }}"
           min="{{ $min }}"
           @if ($max !== null) max="{{ $max }}" @endif
           class="{{ $inputSize }} border-x border-zinc-300 bg-transparent text-center text-zinc-900 [appearance:textfield] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:border-zinc-700 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
           aria-label="Quantity" />

    <button type="button"
            x-on:click="increment()"
            x-bind:disabled="max !== null && value >= max"
            class="flex {{ $buttonSize }} items-center justify-center text-zinc-600 transition hover:text-zinc-900 disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-300 dark:hover:text-white"
            aria-label="Increase quantity">
        <span aria-hidden="true">&plus;</span>
    </button>
</div>
