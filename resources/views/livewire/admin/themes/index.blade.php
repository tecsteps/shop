<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Themes</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Themes</flux:heading>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($themes as $theme)
            <div wire:key="theme-{{ $theme->id }}" class="rounded-lg border bg-white dark:bg-zinc-900 overflow-hidden {{ $theme->status->value === 'published' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                {{-- Thumbnail placeholder --}}
                <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                    <flux:icon name="paint-brush" variant="outline" class="size-12 text-zinc-400" />
                </div>

                {{-- Info --}}
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <flux:heading size="md">{{ $theme->name }}</flux:heading>
                            @if ($theme->version)
                                <flux:text class="text-zinc-500 text-xs">v{{ $theme->version }}</flux:text>
                            @endif
                        </div>
                        <flux:badge size="sm" :color="$theme->status->value === 'published' ? 'green' : 'zinc'">
                            {{ ucfirst($theme->status->value) }}
                        </flux:badge>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 mt-4">
                        <flux:button href="{{ route('admin.themes.editor', $theme) }}" wire:navigate variant="primary" size="sm">
                            Customize
                        </flux:button>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            <flux:menu>
                                @if ($theme->status->value !== 'published')
                                    <flux:menu.item wire:click="activate({{ $theme->id }})" icon="check-circle">Publish</flux:menu.item>
                                @else
                                    <flux:menu.item wire:click="deactivate({{ $theme->id }})" icon="x-circle">Unpublish</flux:menu.item>
                                @endif
                                <flux:menu.item wire:click="duplicateTheme({{ $theme->id }})" icon="document-duplicate">Duplicate</flux:menu.item>
                                <flux:separator />
                                <flux:menu.item wire:click="deleteTheme({{ $theme->id }})" wire:confirm="Are you sure you want to delete this theme?" variant="danger" icon="trash">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <flux:icon name="paint-brush" variant="outline" class="mx-auto size-12 text-zinc-400" />
                <flux:heading size="md" class="mt-4">No themes</flux:heading>
                <flux:text class="mt-1 text-zinc-500">No themes have been created for this store yet.</flux:text>
            </div>
        @endforelse
    </div>
</div>
