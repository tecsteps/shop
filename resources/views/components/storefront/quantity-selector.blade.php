@props(['value' => 1, 'min' => 1, 'max' => null, 'model' => null, 'increment' => null, 'decrement' => null, 'compact' => false, 'label' => 'Quantity'])

@php
    $buttonClass = $compact ? 'h-8 w-8' : 'h-10 w-10';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded border border-zinc-300 dark:border-zinc-700']) }}>
    <button type="button" wire:click="{{ $decrement }}" wire:loading.attr="disabled" class="flex {{ $buttonClass }} items-center justify-center disabled:cursor-not-allowed disabled:opacity-50" aria-label="Decrease quantity" @disabled($value <= $min)>
        <span aria-hidden="true">-</span>
    </button>
    <input type="number" wire:model.live="{{ $model }}" min="{{ $min }}" @if($max !== null) max="{{ $max }}" @endif aria-label="{{ $label }}" class="{{ $compact ? 'h-8 w-12' : 'h-10 w-14' }} border-x border-zinc-300 bg-transparent text-center dark:border-zinc-700" />
    <button type="button" wire:click="{{ $increment }}" wire:loading.attr="disabled" class="flex {{ $buttonClass }} items-center justify-center disabled:cursor-not-allowed disabled:opacity-50" aria-label="Increase quantity" @disabled($max !== null && $value >= $max)>
        <span aria-hidden="true">+</span>
    </button>
</div>
