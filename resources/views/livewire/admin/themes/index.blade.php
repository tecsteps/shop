<div class="space-y-4">
    <flux:heading size="xl">Themes</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($themes as $theme)
            <div wire:key="theme-{{ $theme->id }}" class="flex flex-col rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                <div class="aspect-video rounded bg-neutral-100 dark:bg-neutral-800"></div>
                <div class="mt-3 flex-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ $theme->name }}</div>
                            <div class="text-xs text-neutral-500">v{{ $theme->version }}</div>
                        </div>
                        <flux:badge size="sm" color="{{ $theme->status->value === 'published' ? 'green' : 'zinc' }}">
                            {{ $theme->status->value }}
                        </flux:badge>
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    @if ($theme->status->value !== 'published')
                        <flux:button size="sm" variant="primary" wire:click="publish({{ $theme->id }})">Publish</flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded border border-dashed border-neutral-200 p-8 text-center text-neutral-500 dark:border-neutral-800">No themes found.</div>
        @endforelse
    </div>
</div>
