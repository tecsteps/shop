<div>
    <flux:heading size="xl">Themes</flux:heading>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->themes as $theme)
            <flux:card
                :class="$theme->status === 'published' ? 'overflow-hidden ring-2 ring-blue-500' : 'overflow-hidden'"
            >
                {{-- Thumbnail --}}
                <div class="flex aspect-video items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                    <div class="flex flex-col items-center gap-2 text-zinc-300 dark:text-zinc-600">
                        <flux:icon.paint-brush class="size-10" />
                        <span class="text-xs">{{ $theme->name }}</span>
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex items-start justify-between gap-3 p-5">
                    <div>
                        <flux:heading size="md">{{ $theme->name }}</flux:heading>
                        <flux:text>v{{ $theme->version }}</flux:text>
                    </div>
                    <flux:badge :color="$theme->status === 'published' ? 'green' : 'zinc'" size="sm">
                        {{ ucfirst($theme->status) }}
                    </flux:badge>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="route('admin.themes.editor', $theme)"
                        wire:navigate
                        class="flex-1"
                    >
                        Customize
                    </flux:button>

                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Theme actions" />

                        <flux:menu>
                            <flux:menu.item icon="eye" :href="route('storefront.home')" target="_blank">Preview</flux:menu.item>

                            @if ($theme->status !== 'published')
                                <flux:menu.item icon="arrow-up-tray" wire:click="publishTheme({{ $theme->id }})">Publish</flux:menu.item>
                            @endif

                            <flux:menu.item icon="document-duplicate" wire:click="duplicateTheme({{ $theme->id }})">Duplicate</flux:menu.item>

                            <flux:menu.separator />

                            <flux:menu.item variant="danger" icon="trash" wire:click="deleteTheme({{ $theme->id }})">Delete</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </flux:card>
        @empty
            <div class="col-span-full">
                <flux:card class="p-10 text-center">
                    <flux:heading size="lg">No themes yet</flux:heading>
                    <flux:text class="mt-1">Themes will appear here once created.</flux:text>
                </flux:card>
            </div>
        @endforelse
    </div>
</div>
