<div class="space-y-4">
    <flux:heading size="xl">Themes</flux:heading>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($themes as $theme)
            <div wire:key="theme-{{ $theme->id }}" class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">{{ $theme->name }}</flux:heading>
                    <flux:badge color="{{ $theme->isPublished() ? 'green' : 'zinc' }}">{{ $theme->status?->value }}</flux:badge>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <flux:button size="xs" href="{{ route('admin.themes.editor', $theme) }}" wire:navigate>Edit</flux:button>
                    @if (! $theme->isPublished())
                        <flux:button size="xs" variant="primary" wire:click="publish({{ $theme->id }})">Publish</flux:button>
                    @endif
                    <flux:button size="xs" wire:click="duplicate({{ $theme->id }})">Duplicate</flux:button>
                    @if (! $theme->isPublished())
                        <flux:button size="xs" variant="danger" wire:click="delete({{ $theme->id }})" wire:confirm="Delete this theme?">Delete</flux:button>
                    @endif
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">No themes yet.</flux:text>
        @endforelse
    </div>
</div>
