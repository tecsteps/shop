<div>
    <flux:heading size="xl" class="mb-6">Themes</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($this->themes as $theme)
            <div
                wire:key="theme-{{ $theme->id }}"
                class="border rounded-lg overflow-hidden {{ $theme->is_active ? 'ring-2 ring-blue-500' : 'border-zinc-200 dark:border-zinc-700' }}"
            >
                {{-- Preview area --}}
                <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                    <flux:icon name="paint-brush" class="size-12 text-zinc-400 dark:text-zinc-500" />
                </div>

                {{-- Info --}}
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="md">{{ $theme->name }}</flux:heading>
                        @php
                            $statusColor = $theme->status->value === 'published' ? 'green' : 'zinc';
                        @endphp
                        <flux:badge size="sm" :color="$statusColor">{{ ucfirst($theme->status->value) }}</flux:badge>
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <flux:button size="sm" variant="primary" :href="route('admin.themes.editor', $theme)" wire:navigate>
                            Customize
                        </flux:button>

                        <flux:dropdown>
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />

                            <flux:menu>
                                @if (!$theme->is_active)
                                    <flux:menu.item wire:click="publishTheme({{ $theme->id }})">Publish</flux:menu.item>
                                @endif
                                <flux:menu.item wire:click="duplicateTheme({{ $theme->id }})">Duplicate</flux:menu.item>
                                @if (!$theme->is_active)
                                    <flux:separator />
                                    <flux:menu.item wire:click="deleteTheme({{ $theme->id }})" wire:confirm="Delete this theme?" class="text-red-600 dark:text-red-400">Delete</flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <flux:icon name="paint-brush" class="size-12 mx-auto text-zinc-400 dark:text-zinc-500 mb-4" />
                <flux:heading size="lg">No themes</flux:heading>
                <flux:text class="mt-1">No themes have been created for this store.</flux:text>
            </div>
        @endforelse
    </div>
</div>
