@props(['value' => 1, 'min' => 1, 'max' => null, 'wireModel' => '', 'compact' => false])

@php
    $btnSize = $compact ? 'w-8 h-8' : 'w-10 h-10';
    $inputWidth = $compact ? 'w-12' : 'w-14';
    $inputHeight = $compact ? 'h-8' : 'h-10';
@endphp

<div class="inline-flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
    <button type="button"
            wire:click="decrementQuantity"
            class="{{ $btnSize }} flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
            @if($value <= $min) disabled @endif
            aria-label="Decrease quantity">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
    </button>
    <input type="number"
           wire:model="{{ $wireModel }}"
           min="{{ $min }}"
           @if($max) max="{{ $max }}" @endif
           class="{{ $inputWidth }} {{ $inputHeight }} text-center border-x border-gray-300 dark:border-gray-600 bg-transparent text-sm font-medium text-gray-900 dark:text-white focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
           aria-label="Quantity">
    <button type="button"
            wire:click="incrementQuantity"
            class="{{ $btnSize }} flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
            @if($max && $value >= $max) disabled @endif
            aria-label="Increase quantity">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    </button>
</div>
