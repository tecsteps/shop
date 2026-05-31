<div>
    <x-admin.breadcrumbs :items="[['label' => __('Apps')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Apps') }}</flux:heading>

    @if ($this->installedApps->isEmpty())
        <x-admin.card class="py-16 text-center">
            <flux:icon.squares-2x2 class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">{{ __('No apps installed') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Browse the app marketplace to extend your store.') }}</flux:text>
        </x-admin.card>
    @else
        <div class="space-y-4">
            @foreach ($this->installedApps as $installation)
                <x-admin.card wire:key="app-{{ $installation->id }}">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.squares-2x2 class="size-6 text-zinc-500" />
                        </div>
                        <div class="flex-1">
                            <flux:link :href="route('admin.apps.show', $installation->id)" wire:navigate class="font-medium">
                                {{ $installation->app?->name ?? __('Unknown app') }}
                            </flux:link>
                            <flux:text class="text-sm">{{ __('Installed') }} {{ $installation->installed_at?->diffForHumans() }}</flux:text>
                        </div>
                        <flux:badge :color="$installation->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($installation->status->value) }}</flux:badge>
                    </div>
                </x-admin.card>
            @endforeach
        </div>
    @endif
</div>
