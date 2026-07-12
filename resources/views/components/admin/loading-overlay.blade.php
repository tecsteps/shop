@props([
    'target' => null,
    'label' => 'Loading',
    'delay' => true,
])

<div
    {{ $attributes->class('admin-loading-overlay') }}
    @if ($delay) wire:loading.delay.flex @else wire:loading.flex @endif
    @if (filled($target)) wire:target="{{ $target }}" @endif
    role="status"
    aria-live="polite"
>
    <svg aria-hidden="true" class="size-6 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
        <path class="opacity-90" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
    </svg>
    <span class="sr-only">{{ $label }}</span>
</div>
