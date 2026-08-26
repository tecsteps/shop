@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    'decrement' => null,
    'increment' => null,
    'compact' => false,
    'disabled' => false,
])

@php
    $size = $compact ? 'h-8 w-8' : 'h-10 w-10';
    $inputWidth = $compact ? 'w-10' : 'w-14';
    $buttonBase = 'inline-flex items-center justify-center text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-zinc-800 dark:hover:text-zinc-200';
    $rounded = $compact ? 'rounded-md' : 'rounded-lg';
    $atMin = $value <= $min;
    $atMax = $max !== null && $value >= (int) $max;
@endphp

<div class="inline-flex items-stretch overflow-hidden border border-zinc-200 {{ $rounded }} {{ $disabled ? 'opacity-50' : '' }} dark:border-zinc-700">
    <button
        type="button"
        aria-label="Decrease quantity"
        @disabled($disabled || $atMin)
        @if ($decrement !== null) wire:click="{{ $decrement }}" @endif
        class="{{ $buttonBase }} {{ $size }} rounded-l-{{ $compact ? 'md' : 'lg' }}"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14" />
        </svg>
    </button>

    <input
        type="number"
        inputmode="numeric"
        min="{{ $min }}"
        @if ($max !== null) max="{{ $max }}" @endif
        value="{{ $value }}"
        readonly
        aria-label="Quantity"
        class="{{ $inputWidth }} border-x border-zinc-200 bg-transparent text-center text-sm font-medium text-zinc-900 focus:outline-none dark:border-zinc-700 dark:text-white"
    />

    <button
        type="button"
        aria-label="Increase quantity"
        @disabled($disabled || $atMax)
        @if ($increment !== null) wire:click="{{ $increment }}" @endif
        class="{{ $buttonBase }} {{ $size }} rounded-r-{{ $compact ? 'md' : 'lg' }}"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 5v14M5 12h14" />
        </svg>
    </button>
</div>
