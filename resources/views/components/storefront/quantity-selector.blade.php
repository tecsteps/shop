@props(['value' => 1, 'min' => 1, 'max' => null, 'wireModel', 'compact' => false])

@php
    $size = $compact ? 'h-8 w-8 text-xs' : 'h-10 w-10 text-sm';
    $inputSize = $compact ? 'h-8 w-12 text-xs' : 'h-10 w-14 text-sm';
@endphp

<div {{ $attributes->class(['inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600']) }}>
    <button type="button"
            wire:click="$set('{{ $wireModel }}', Math.max({{ $min }}, $wire.{{ $wireModel }} - 1))"
            class="{{ $size }} flex items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-50"
            aria-label="Decrease quantity"
            @if($value <= $min) disabled @endif>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
    </button>

    <input type="number"
           wire:model.live="{{ $wireModel }}"
           min="{{ $min }}"
           @if($max) max="{{ $max }}" @endif
           class="{{ $inputSize }} border-x border-gray-300 text-center text-gray-900 dark:border-gray-600 dark:bg-transparent dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
           aria-label="Quantity">

    <button type="button"
            wire:click="$set('{{ $wireModel }}', $wire.{{ $wireModel }} + 1)"
            class="{{ $size }} flex items-center justify-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-50"
            aria-label="Increase quantity"
            @if($max && $value >= $max) disabled @endif>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>
</div>
