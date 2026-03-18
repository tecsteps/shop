<div>
    <flux:heading size="xl" class="mb-6">{{ __('Apps') }}</flux:heading>

    @if($this->installedApps->isEmpty())
        <div class="text-center py-12">
            <flux:icon name="squares-2x2" class="mx-auto h-12 w-12 text-zinc-400" />
            <flux:heading size="md" class="mt-4">{{ __('No apps installed') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('Apps extend your store with additional features and integrations.') }}</flux:text>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->installedApps as $installation)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="squares-2x2" class="w-5 h-5 text-zinc-500" />
                        </div>
                        <div>
                            <flux:heading size="md">{{ $installation->app->name }}</flux:heading>
                            <flux:text class="text-zinc-500">{{ __('Installed') }} {{ $installation->installed_at?->diffForHumans() }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:badge :color="$installation->status === 'active' ? 'green' : 'zinc'">
                            {{ ucfirst($installation->status) }}
                        </flux:badge>
                        <flux:button size="sm" variant="danger" wire:click="uninstallApp({{ $installation->id }})" wire:confirm="{{ __('Are you sure you want to uninstall this app?') }}">
                            {{ __('Uninstall') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
