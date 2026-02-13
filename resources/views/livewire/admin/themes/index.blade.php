<div class="space-y-6">
    <flux:heading size="xl">Themes</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($themes as $theme)
            <div wire:key="theme-{{ $theme->id }}" class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="md">{{ $theme->name }}</flux:heading>
                    <flux:badge size="sm">{{ ucfirst($theme->status->value) }}</flux:badge>
                </div>
                <flux:text class="mb-4 text-sm">Version: {{ $theme->version }}</flux:text>
                <div class="flex items-center gap-2">
                    @if ($theme->status->value !== 'published')
                        <flux:button wire:click="publish({{ $theme->id }})" variant="primary" size="sm">Publish</flux:button>
                    @endif
                    <flux:button wire:click="duplicate({{ $theme->id }})" variant="ghost" size="sm">Duplicate</flux:button>
                </div>
            </div>
        @endforeach
    </div>
</div>
