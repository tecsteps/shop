{{--
    Quantity stepper with increment/decrement buttons (spec 04 §16).

    Props:
    - value: int — current quantity (default: 1)
    - min: int — minimum allowed value (default: 1)
    - max: int|null — maximum allowed value (null = unlimited)
    - wireModel: string (required) — Livewire model binding for the input
    - compact: bool — smaller variant for the cart drawer
--}}
@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    'wireModel',
    'compact' => false,
])

@php
    $buttonSize = $compact ? 'size-8' : 'size-10';
    $inputSize = $compact ? 'h-8 w-10' : 'h-10 w-14';
    $decreased = max($min, (int) $value - 1);
    $increased = $max === null ? (int) $value + 1 : min((int) $max, (int) $value + 1);
@endphp

<div {{ $attributes->class('inline-flex items-center rounded-md border border-gray-300 dark:border-gray-700') }}>
    <button type="button"
            wire:click="$set('{{ $wireModel }}', {{ $decreased }})"
            @disabled((int) $value <= (int) $min)
            class="{{ $buttonSize }} inline-flex items-center justify-center text-gray-600 transition hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-40 focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:text-gray-300 dark:hover:text-white"
            aria-label="Decrease quantity">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
    </button>
    <label for="quantity-{{ $wireModel }}" class="sr-only">Quantity</label>
    <input id="quantity-{{ $wireModel }}"
           type="number"
           wire:model.live="{{ $wireModel }}"
           min="{{ $min }}"
           @if ($max !== null) max="{{ $max }}" @endif
           class="{{ $inputSize }} border-x border-gray-300 bg-transparent text-center text-sm text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:border-gray-700 dark:text-white">
    <button type="button"
            wire:click="$set('{{ $wireModel }}', {{ $increased }})"
            @disabled($max !== null && (int) $value >= (int) $max)
            class="{{ $buttonSize }} inline-flex items-center justify-center text-gray-600 transition hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-40 focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:text-gray-300 dark:hover:text-white"
            aria-label="Increase quantity">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>
</div>
