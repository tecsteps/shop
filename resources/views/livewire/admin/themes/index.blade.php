<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Themes</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Themes</flux:heading>

    @if ($themes->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-6 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text variant="subtle">No themes yet.</flux:text>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($themes as $theme)
                <div
                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 {{ $theme->status->value === 'published' ? 'ring-2 ring-blue-500' : '' }}"
                    wire:key="theme-{{ $theme->getKey() }}"
                >
                    <div class="flex aspect-video items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.photo class="size-12 text-zinc-400" variant="outline" />
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading size="md">{{ $theme->name }}</flux:heading>
                            <span class="text-xs text-zinc-500">v{{ $theme->version }}</span>
                        </div>
                        <div class="mt-2">
                            <flux:badge :color="$theme->status->value === 'published' ? 'green' : 'zinc'">{{ ucfirst($theme->status->value) }}</flux:badge>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <flux:button variant="primary" size="sm" href="{{ route('admin.themes.editor', $theme) }}" wire:navigate>Customize</flux:button>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" aria-label="More actions">
                                    <flux:icon.ellipsis-horizontal class="size-5" variant="outline" />
                                </flux:button>
                                <flux:menu>
                                    @if ($theme->status->value !== 'published')
                                        <flux:menu.item wire:click="publishTheme({{ $theme->getKey() }})">Publish</flux:menu.item>
                                    @endif
                                    <flux:menu.item wire:click="duplicateTheme({{ $theme->getKey() }})">Duplicate</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="confirmDelete({{ $theme->getKey() }})" variant="danger">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete this theme?</flux:heading>
        <flux:text class="mt-2">The theme and its settings will be permanently removed.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deleteTheme">Delete</flux:button>
        </div>
    </flux:modal>
</div>
