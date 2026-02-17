@props(['label', 'value', 'change' => 0])

<div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</flux:text>
    <flux:heading size="xl" class="mt-1">{{ $value }}</flux:heading>
    <div class="mt-2 flex items-center gap-1">
        @if ((float) $change > 0)
            <flux:badge color="green" size="sm">+{{ $change }}%</flux:badge>
            <flux:icon name="arrow-trending-up" variant="mini" class="h-4 w-4 text-green-500" />
        @elseif ((float) $change < 0)
            <flux:badge color="red" size="sm">{{ $change }}%</flux:badge>
            <flux:icon name="arrow-trending-down" variant="mini" class="h-4 w-4 text-red-500" />
        @else
            <flux:badge size="sm">0%</flux:badge>
        @endif
    </div>
</div>
