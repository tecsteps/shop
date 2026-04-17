@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    'wireModel' => '',
    'compact' => false,
])

@php
    $buttonSize = $compact ? 'h-8 w-8' : 'h-10 w-10';
    $inputWidth = $compact ? 'w-12' : 'w-14';
    $inputHeight = $compact ? 'h-8' : 'h-10';
@endphp

<div {{ $attributes->class(['inline-flex items-center rounded-md border border-zinc-300 dark:border-zinc-600']) }}>
    <button type="button"
            wire:click="decrementQuantity"
            class="{{ $buttonSize }} inline-flex items-center justify-center text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            {{ $value <= $min ? 'disabled' : '' }}
            aria-label="Decrease quantity">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
    </button>

    <input type="number"
           wire:model.live="{{ $wireModel }}"
           value="{{ $value }}"
           min="{{ $min }}"
           @if($max) max="{{ $max }}" @endif
           class="{{ $inputWidth }} {{ $inputHeight }} border-x border-zinc-300 dark:border-zinc-600 bg-transparent text-center text-sm text-zinc-900 dark:text-white focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
           aria-label="Quantity">

    <button type="button"
            wire:click="incrementQuantity"
            class="{{ $buttonSize }} inline-flex items-center justify-center text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            {{ $max !== null && $value >= $max ? 'disabled' : '' }}
            aria-label="Increase quantity">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>
</div>
