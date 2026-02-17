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

<div class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600">
    <button type="button"
            wire:click="$set('{{ $wireModel }}', Math.max({{ $min }}, $wire.{{ $wireModel }} - 1))"
            class="{{ $buttonSize }} flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:bg-gray-800"
            aria-label="Decrease quantity"
            @if($value <= $min) disabled @endif>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
    </button>
    <input type="number"
           wire:model.live="{{ $wireModel }}"
           min="{{ $min }}"
           @if($max) max="{{ $max }}" @endif
           class="{{ $inputWidth }} {{ $inputHeight }} border-x border-gray-300 bg-transparent text-center text-sm text-gray-900 [appearance:textfield] focus:outline-none dark:border-gray-600 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
           aria-label="Quantity">
    <button type="button"
            wire:click="$set('{{ $wireModel }}', $wire.{{ $wireModel }} + 1)"
            class="{{ $buttonSize }} flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-400 dark:hover:bg-gray-800"
            aria-label="Increase quantity"
            @if($max && $value >= $max) disabled @endif>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>
</div>
