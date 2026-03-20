@php
    $value = $value ?? 1;
    $min = $min ?? 1;
    $max = $max ?? 99;
    $wireModel = $wireModel ?? null;
    $isCompact = isset($compact) && $compact;
    $btnSize = $isCompact ? 'h-8 w-8' : 'h-10 w-10';
@endphp

<div class="flex items-center rounded-md border border-zinc-300 dark:border-zinc-600">
    <button type="button"
            @if($wireModel)
                wire:click="$set('{{ $wireModel }}', Math.max({{ $min }}, $wire.{{ $wireModel }} - 1))"
            @endif
            @disabled($value <= $min)
            class="{{ $btnSize }} flex items-center justify-center text-zinc-600 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-zinc-400 dark:hover:bg-zinc-700"
            aria-label="Decrease quantity">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
    </button>

    <input type="number"
           @if($wireModel) wire:model="{{ $wireModel }}" @endif
           value="{{ $value }}"
           min="{{ $min }}"
           max="{{ $max }}"
           class="h-10 w-12 border-x border-zinc-300 bg-transparent text-center text-sm text-zinc-900 [appearance:textfield] focus:outline-none dark:border-zinc-600 dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
           aria-label="Quantity">

    <button type="button"
            @if($wireModel)
                wire:click="$set('{{ $wireModel }}', Math.min({{ $max }}, $wire.{{ $wireModel }} + 1))"
            @endif
            @disabled($value >= $max)
            class="{{ $btnSize }} flex items-center justify-center text-zinc-600 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-zinc-400 dark:hover:bg-zinc-700"
            aria-label="Increase quantity">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
    </button>
</div>
