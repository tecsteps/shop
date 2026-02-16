@props(['value' => 1, 'min' => 1, 'max' => null, 'wireModel' => '', 'compact' => false])

@php
    $size = $compact ? 'h-8 w-8' : 'h-10 w-10';
    $inputWidth = $compact ? 'w-12' : 'w-14';
@endphp

<div class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-700">
    <button
        type="button"
        wire:click="decrementQuantity"
        @if($value <= $min) disabled @endif
        class="{{ $size }} inline-flex items-center justify-center text-gray-600 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:text-white"
        aria-label="Decrease quantity"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
    </button>
    <input
        type="number"
        value="{{ $value }}"
        wire:model.live="{{ $wireModel }}"
        min="{{ $min }}"
        @if($max) max="{{ $max }}" @endif
        class="{{ $inputWidth }} h-full border-x border-gray-300 bg-transparent text-center text-sm dark:border-gray-700 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        aria-label="Quantity"
    >
    <button
        type="button"
        wire:click="incrementQuantity"
        @if($max && $value >= $max) disabled @endif
        class="{{ $size }} inline-flex items-center justify-center text-gray-600 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:text-white"
        aria-label="Increase quantity"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
    </button>
</div>
