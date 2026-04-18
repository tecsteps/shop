@props([
    'name' => 'quantity',
    'value' => 1,
    'min' => 1,
    'max' => 99,
    'model' => null,
])

@php
    $wire = $model ? 'wire:model.live='.$model : '';
@endphp

<div {{ $attributes->class(['inline-flex items-center rounded-md border border-zinc-200 dark:border-zinc-700']) }} role="group" aria-label="Quantity">
    <button type="button"
            class="flex h-9 w-9 items-center justify-center text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
            aria-label="Decrease quantity"
            @if ($model) wire:click="$set('{{ $model }}', Math.max({{ (int) $min }}, Number($wire.{{ $model }}) - 1))" @endif>
        <flux:icon name="minus" class="size-4" />
    </button>
    <input type="number"
           name="{{ $name }}"
           value="{{ $value }}"
           min="{{ $min }}"
           max="{{ $max }}"
           {{ $wire }}
           class="h-9 w-12 border-0 bg-transparent text-center text-sm focus:ring-0 dark:text-zinc-100"
           aria-label="Quantity" />
    <button type="button"
            class="flex h-9 w-9 items-center justify-center text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
            aria-label="Increase quantity"
            @if ($model) wire:click="$set('{{ $model }}', Math.min({{ (int) $max }}, Number($wire.{{ $model }}) + 1))" @endif>
        <flux:icon name="plus" class="size-4" />
    </button>
</div>
