@props([
    'wireModel' => 'quantity',
    'min' => 1,
    'max' => null,
    'disabled' => false,
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center border border-zinc-300 dark:border-zinc-600 rounded-lg']) }}>
    <button
        type="button"
        wire:click="decrementQuantity"
        class="flex items-center justify-center w-10 h-10 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        @if ($disabled) disabled @endif
        aria-label="Decrease quantity"
    >
        <flux:icon name="minus" class="size-4" />
    </button>

    <input
        type="number"
        wire:model.live="{{ $wireModel }}"
        min="{{ $min }}"
        @if ($max) max="{{ $max }}" @endif
        class="w-14 h-10 text-center text-sm font-medium bg-transparent border-x border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
        @if ($disabled) disabled @endif
        aria-label="Quantity"
    />

    <button
        type="button"
        wire:click="incrementQuantity"
        class="flex items-center justify-center w-10 h-10 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        @if ($disabled) disabled @endif
        aria-label="Increase quantity"
    >
        <flux:icon name="plus" class="size-4" />
    </button>
</div>
