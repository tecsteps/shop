<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Apps')]]" />

    <flux:heading size="xl" level="1">{{ __('Apps') }}</flux:heading>

    <div>
        <flux:heading size="lg">{{ __('Installed apps') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Apps connected to this store.') }}</flux:text>
    </div>

    @if ($this->installedApps->isEmpty())
        <x-admin.card>
            <div class="flex flex-col items-center gap-2 py-8 text-center" data-test="apps-empty-state">
                <flux:icon name="squares-2x2" class="size-8 text-zinc-400" />
                <flux:heading size="md">{{ __('No apps installed') }}</flux:heading>
                <flux:text>{{ __('Install an app from the directory below to extend your store.') }}</flux:text>
            </div>
        </x-admin.card>
    @else
        <div class="space-y-3">
            @foreach ($this->installedApps as $installation)
                <x-admin.card wire:key="installation-{{ $installation->id }}">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="squares-2x2" class="size-6 text-zinc-500 dark:text-zinc-400" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.apps.show', $installation) }}" wire:navigate class="hover:underline">
                                <flux:heading size="md">{{ $installation->app->name }}</flux:heading>
                            </a>
                            <flux:text class="text-sm">
                                {{ __('Installed :time', ['time' => $installation->installed_at?->diffForHumans() ?? __('recently')]) }}
                            </flux:text>
                        </div>

                        <x-admin.status-badge :status="$installation->status" />

                        <flux:button
                            size="sm"
                            variant="ghost"
                            wire:click="uninstallApp({{ $installation->id }})"
                            wire:confirm="{{ __('Uninstall this app? Its webhook subscriptions will be disabled.') }}"
                            data-test="uninstall-app-{{ $installation->id }}"
                        >
                            {{ __('Uninstall') }}
                        </flux:button>
                    </div>
                </x-admin.card>
            @endforeach
        </div>
    @endif

    <flux:separator />

    <div>
        <flux:heading size="lg">{{ __('Available apps') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Apps from the platform directory that can be installed on this store.') }}</flux:text>
    </div>

    @if ($this->availableApps->isEmpty())
        <x-admin.card>
            <flux:text class="py-4 text-center">{{ __('No more apps available to install.') }}</flux:text>
        </x-admin.card>
    @else
        <div class="space-y-3">
            @foreach ($this->availableApps as $app)
                <x-admin.card wire:key="app-{{ $app->id }}">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="squares-2x2" class="size-6 text-zinc-500 dark:text-zinc-400" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <flux:heading size="md">{{ $app->name }}</flux:heading>
                        </div>

                        <flux:button
                            size="sm"
                            variant="primary"
                            wire:click="installApp({{ $app->id }})"
                            data-test="install-app-{{ $app->id }}"
                        >
                            {{ __('Install') }}
                        </flux:button>
                    </div>
                </x-admin.card>
            @endforeach
        </div>
    @endif
</div>
