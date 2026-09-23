@php
    $quantityValue = max((int) ($value ?? 1), (int) ($min ?? 1));
    $quantityMin = (int) ($min ?? 1);
    $quantityMax = isset($max) ? (int) $max : null;
    $isCompact = (bool) ($compact ?? false);
    $buttonSize = $isCompact ? 'size-9' : 'size-11';
    $fieldSize = $isCompact ? 'h-9 w-11' : 'h-11 w-14';
@endphp
<div class="inline-flex items-center rounded-xl border border-stone-300 bg-white dark:border-stone-700 dark:bg-stone-900" aria-label="Quantity controls">
    <button type="button" wire:click="{{ $decreaseAction ?? 'decreaseQuantity' }}" @disabled($quantityValue <= $quantityMin) class="{{ $buttonSize }} inline-flex shrink-0 items-center justify-center rounded-l-xl text-stone-700 hover:bg-stone-100 focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-blue-700 disabled:cursor-not-allowed disabled:opacity-40 dark:text-stone-100 dark:hover:bg-stone-800" aria-label="Decrease quantity">
        @include('storefront.components.icon', ['name' => 'minus', 'class' => 'size-4'])
    </button>
    <input type="number" min="{{ $quantityMin }}" @if ($quantityMax !== null) max="{{ $quantityMax }}" @endif value="{{ $quantityValue }}" wire:model.live="{{ $wireModel ?? 'quantity' }}" class="{{ $fieldSize }} border-x border-stone-200 bg-transparent text-center text-sm tabular-nums text-stone-950 focus:z-10 focus:outline-2 focus:outline-blue-700 dark:border-stone-700 dark:text-white" aria-label="Quantity">
    <button type="button" wire:click="{{ $increaseAction ?? 'increaseQuantity' }}" @disabled($quantityMax !== null && $quantityValue >= $quantityMax) class="{{ $buttonSize }} inline-flex shrink-0 items-center justify-center rounded-r-xl text-stone-700 hover:bg-stone-100 focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-blue-700 disabled:cursor-not-allowed disabled:opacity-40 dark:text-stone-100 dark:hover:bg-stone-800" aria-label="Increase quantity">
        @include('storefront.components.icon', ['name' => 'plus', 'class' => 'size-4'])
    </button>
</div>
