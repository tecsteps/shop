<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Themes') }}</flux:heading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->themes as $theme)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
                <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                    <flux:icon name="paint-brush" class="size-12 text-zinc-300 dark:text-zinc-600" />
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $theme->name }}</h3>
                        <flux:badge size="sm" :color="$theme->status === \App\Enums\ThemeStatus::Published ? 'green' : 'zinc'">
                            {{ ucfirst($theme->status->value) }}
                        </flux:badge>
                    </div>
                    <p class="text-sm text-zinc-500 mb-4">{{ __('Version') }}: {{ $theme->version }}</p>

                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="ghost" :href="route('admin.themes.editor', $theme)" wire:navigate icon="pencil-square">
                            {{ __('Edit') }}
                        </flux:button>

                        @if($theme->status !== \App\Enums\ThemeStatus::Published)
                            <flux:button size="sm" variant="primary" wire:click="publish({{ $theme->id }})">
                                {{ __('Publish') }}
                            </flux:button>
                        @else
                            <flux:badge size="sm" color="green" class="!text-xs">{{ __('Active') }}</flux:badge>
                        @endif

                        <flux:button size="sm" variant="ghost" wire:click="duplicate({{ $theme->id }})" icon="document-duplicate" />

                        @if($theme->status !== \App\Enums\ThemeStatus::Published)
                            <flux:button size="sm" variant="ghost" wire:click="deleteTheme({{ $theme->id }})" wire:confirm="{{ __('Are you sure?') }}" icon="trash" />
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($this->themes->isEmpty())
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-12 text-center">
            <flux:heading size="lg">{{ __('No themes found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('Create a theme to customize your storefront.') }}</flux:text>
        </div>
    @endif
</div>
