@props([
    'value' => 1,
    'min' => 1,
    'max' => null,
    'wireModel' => null,
    'compact' => false,
])

@php
    $size = $compact ? 'size-8' : 'size-10';
    $inputWidth = $compact ? 'w-10' : 'w-14';
    $atMin = $value <= $min;
    $atMax = $max !== null && $value >= $max;
@endphp

<div {{ $attributes->class(['inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-700']) }}>
    <button
        type="button"
        wire:click="$set('{{ $wireModel }}', {{ max($min, $value - 1) }})"
        @disabled($atMin)
        aria-label="Decrease quantity"
        class="{{ $size }} flex items-center justify-center rounded-l-lg text-zinc-600 hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
    >
        <flux:icon name="minus" class="size-4" />
    </button>

    <label for="quantity-{{ $wireModel }}" class="sr-only">Quantity</label>
    <input
        id="quantity-{{ $wireModel }}"
        type="number"
        wire:model.live="{{ $wireModel }}"
        min="{{ $min }}"
        @if ($max !== null) max="{{ $max }}" @endif
        class="{{ $inputWidth }} {{ $size }} border-x border-zinc-300 bg-transparent text-center text-sm font-medium text-zinc-900 focus:outline-none dark:border-zinc-700 dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
    />

    <button
        type="button"
        wire:click="$set('{{ $wireModel }}', {{ $max !== null ? min($max, $value + 1) : $value + 1 }})"
        @disabled($atMax)
        aria-label="Increase quantity"
        class="{{ $size }} flex items-center justify-center rounded-r-lg text-zinc-600 hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
    >
        <flux:icon name="plus" class="size-4" />
    </button>
</div>
