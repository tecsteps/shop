<div>
    <div class="mb-6">
        <flux:heading size="xl">Themes</flux:heading>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->themes as $theme)
            <div @class([
                'rounded-lg border bg-white dark:bg-gray-900 overflow-hidden',
                'ring-2 ring-blue-500 border-blue-300 dark:border-blue-700' => $theme->is_published,
                'border-gray-200 dark:border-gray-700' => !$theme->is_published,
            ]) wire:key="theme-{{ $theme->id }}">
                <div class="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <flux:icon name="paint-brush" variant="outline" class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2">
                        <flux:heading size="md">{{ $theme->name }}</flux:heading>
                        <flux:text class="text-xs text-gray-500">v{{ $theme->version ?? '1.0' }}</flux:text>
                    </div>
                    <div class="mt-1">
                        <flux:badge :color="$theme->is_published ? 'green' : 'zinc'" size="sm">
                            {{ $theme->is_published ? 'Published' : 'Draft' }}
                        </flux:badge>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <flux:button variant="primary" size="sm" href="{{ route('admin.themes.editor', $theme) }}" wire:navigate>
                            Customize
                        </flux:button>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                            <flux:menu>
                                @if (!$theme->is_published)
                                    <flux:menu.item wire:click="publishTheme({{ $theme->id }})">Publish</flux:menu.item>
                                @endif
                                <flux:menu.item wire:click="duplicateTheme({{ $theme->id }})">Duplicate</flux:menu.item>
                                <flux:separator />
                                <flux:menu.item wire:click="deleteTheme({{ $theme->id }})" wire:confirm="Delete this theme?" class="text-red-500">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
