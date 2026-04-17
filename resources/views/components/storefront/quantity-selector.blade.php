@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    'wireModel' => null,
    'compact' => false,
])

@php
    $buttonSize = $compact ? 'h-8 w-8' : 'h-10 w-10';
    $inputWidth = $compact ? 'w-12' : 'w-14';
    $inputHeight = $compact ? 'h-8' : 'h-10';
@endphp

<div class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-700">
    <button type="button"
            @if($wireModel)
                wire:click="decrementQuantity"
            @endif
            @if($value <= $min) disabled @endif
            class="{{ $buttonSize }} flex items-center justify-center text-gray-600 transition-colors hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:text-white"
            aria-label="Decrease quantity">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
    </button>

    <input type="number"
           @if($wireModel)
               wire:model.live="{{ $wireModel }}"
           @endif
           value="{{ $value }}"
           min="{{ $min }}"
           @if($max) max="{{ $max }}" @endif
           class="{{ $inputWidth }} {{ $inputHeight }} border-x border-gray-300 bg-transparent text-center text-sm text-gray-900 [appearance:textfield] focus:outline-none dark:border-gray-700 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
           aria-label="Quantity">

    <button type="button"
            @if($wireModel)
                wire:click="incrementQuantity"
            @endif
            @if($max && $value >= $max) disabled @endif
            class="{{ $buttonSize }} flex items-center justify-center text-gray-600 transition-colors hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:text-white"
            aria-label="Increase quantity">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>
</div>
