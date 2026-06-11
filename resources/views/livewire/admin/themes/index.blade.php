<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Themes')]]" />

    <flux:heading size="xl" level="1">{{ __('Themes') }}</flux:heading>

    @if ($this->themes->isEmpty())
        <x-admin.card class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon name="paint-brush" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('No themes yet') }}</flux:heading>
            <flux:text>{{ __('Themes control the look and feel of your storefront.') }}</flux:text>
        </x-admin.card>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->themes as $theme)
                <div
                    wire:key="theme-{{ $theme->id }}"
                    @class([
                        'flex flex-col overflow-hidden rounded-lg border bg-white dark:bg-zinc-900',
                        'border-zinc-200 dark:border-zinc-700' => $theme->status !== \App\Enums\ThemeStatus::Published,
                        'border-transparent ring-2 ring-blue-500' => $theme->status === \App\Enums\ThemeStatus::Published,
                    ])
                    data-test="theme-card-{{ $theme->id }}"
                >
                    {{-- Thumbnail placeholder --}}
                    <div class="flex aspect-video items-center justify-center bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700">
                        <flux:icon name="paint-brush" class="size-10 text-zinc-400 dark:text-zinc-500" />
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <flux:heading>{{ $theme->name }}</flux:heading>
                                <flux:text class="text-sm">v{{ $theme->version }}</flux:text>
                            </div>
                            <x-admin.status-badge :status="$theme->status" />
                        </div>

                        <div class="mt-auto flex items-center gap-2">
                            <flux:button variant="primary" size="sm" :href="route('admin.themes.editor', $theme)" wire:navigate class="flex-1" data-test="customize-theme-{{ $theme->id }}">
                                {{ __('Customize') }}
                            </flux:button>

                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="{{ __('More actions for :name', ['name' => $theme->name]) }}" data-test="theme-actions-{{ $theme->id }}" />

                                <flux:menu>
                                    @if ($theme->status !== \App\Enums\ThemeStatus::Published)
                                        <flux:menu.item icon="rocket-launch" wire:click="publishTheme({{ $theme->id }})" data-test="publish-theme-{{ $theme->id }}">
                                            {{ __('Publish') }}
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.item icon="document-duplicate" wire:click="duplicateTheme({{ $theme->id }})" data-test="duplicate-theme-{{ $theme->id }}">
                                        {{ __('Duplicate') }}
                                    </flux:menu.item>
                                    @if ($theme->status !== \App\Enums\ThemeStatus::Published)
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="deleteTheme({{ $theme->id }})"
                                            wire:confirm="{{ __('Delete this theme? This cannot be undone.') }}"
                                            data-test="delete-theme-{{ $theme->id }}"
                                        >
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
