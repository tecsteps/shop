<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Themes</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Themes</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($themes as $theme)
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden" wire:key="theme-{{ $theme->id }}">
                <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                    <flux:icon name="paint-brush" variant="outline" class="size-12 text-zinc-400" />
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading size="base">{{ $theme->name }}</flux:heading>
                        <flux:badge size="sm" :color="$theme->status->value === 'published' ? 'green' : 'zinc'">
                            {{ ucfirst($theme->status->value) }}
                        </flux:badge>
                    </div>

                    @if ($theme->version)
                        <flux:text class="text-xs text-zinc-500">v{{ $theme->version }}</flux:text>
                    @endif

                    <div class="flex items-center gap-2">
                        @if ($theme->status->value === 'published')
                            <flux:button wire:click="deactivate({{ $theme->id }})" size="sm" variant="ghost">Deactivate</flux:button>
                        @else
                            <flux:button wire:click="activate({{ $theme->id }})" size="sm" variant="primary">Activate</flux:button>
                        @endif
                        <flux:button href="{{ route('admin.themes.editor', $theme) }}" wire:navigate size="sm" variant="ghost" icon="pencil-square">
                            Customize
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500">No themes found.</flux:text>
            </div>
        @endforelse
    </div>
</div>
