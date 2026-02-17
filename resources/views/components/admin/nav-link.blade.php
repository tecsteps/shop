@props(['route', 'icon', 'label'])

@php
    $isActive = request()->routeIs($route . '*');
@endphp

<a
    href="{{ route($route) }}"
    wire:navigate
    @class([
        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
        'bg-zinc-100 text-zinc-900 dark:bg-zinc-700 dark:text-white' => $isActive,
        'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-700/50 dark:hover:text-white' => ! $isActive,
    ])
>
    <flux:icon :name="$icon" variant="outline" class="h-5 w-5 shrink-0" />
    <span>{{ $label }}</span>
</a>
