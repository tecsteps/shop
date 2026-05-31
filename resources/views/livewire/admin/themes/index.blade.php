<div>
    <x-admin.breadcrumbs :items="[['label' => __('Themes')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Themes') }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->themes as $theme)
            <div @class([
                'overflow-hidden rounded-lg border bg-white dark:bg-zinc-900',
                'border-blue-500 ring-2 ring-blue-500/40' => $theme->isPublished(),
                'border-zinc-200 dark:border-zinc-700' => ! $theme->isPublished(),
            ]) wire:key="theme-{{ $theme->id }}">
                <div class="flex aspect-video items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.paint-brush class="size-10 text-zinc-300 dark:text-zinc-600" />
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="md">{{ $theme->name }}</flux:heading>
                        <flux:text class="text-xs">v{{ $theme->version }}</flux:text>
                    </div>
                    <div class="mt-2">
                        <flux:badge size="sm" :color="$theme->isPublished() ? 'green' : 'zinc'">
                            {{ $theme->isPublished() ? __('Published') : __('Draft') }}
                        </flux:badge>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <flux:button size="sm" variant="primary" :href="route('admin.themes.editor', $theme)" wire:navigate data-test="customize-{{ $theme->id }}">{{ __('Customize') }}</flux:button>
                        <flux:dropdown>
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('More actions')" />
                            <flux:menu>
                                @unless ($theme->isPublished())
                                    <flux:menu.item icon="check" wire:click="publishTheme({{ $theme->id }})">{{ __('Publish') }}</flux:menu.item>
                                @endunless
                                <flux:menu.item icon="document-duplicate" wire:click="duplicateTheme({{ $theme->id }})">{{ __('Duplicate') }}</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="deleteTheme({{ $theme->id }})" wire:confirm="{{ __('Delete this theme?') }}">{{ __('Delete') }}</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </div>
        @empty
            <x-admin.card class="md:col-span-2 lg:col-span-3 text-center">
                <flux:text>{{ __('No themes yet.') }}</flux:text>
            </x-admin.card>
        @endforelse
    </div>
</div>
