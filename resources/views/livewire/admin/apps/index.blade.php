<div class="space-y-6 p-6">
    <flux:heading size="xl">Apps</flux:heading>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Installed ({{ $installedApps->count() }})</flux:heading>
        @if ($installedApps->isEmpty())
            <p class="mt-2 text-sm text-zinc-500">No apps installed.</p>
        @else
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($installedApps as $app)
                    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">{{ $app->name }}</flux:heading>
                            <flux:badge color="green">installed</flux:badge>
                        </div>
                        <p class="mt-2 text-sm text-zinc-500">{{ $app->description }}</p>
                        <div class="mt-4">
                            <flux:button size="sm" variant="danger" wire:click="uninstall({{ $app->id }})" wire:confirm="Uninstall app?">Uninstall</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Marketplace ({{ $marketplaceApps->count() }})</flux:heading>
        @if ($marketplaceApps->isEmpty())
            <p class="mt-2 text-sm text-zinc-500">No apps available in the marketplace.</p>
        @else
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($marketplaceApps as $app)
                    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">{{ $app->name }}</flux:heading>
                            <flux:badge color="zinc">{{ $app->type }}</flux:badge>
                        </div>
                        <p class="mt-2 text-sm text-zinc-500">{{ $app->description }}</p>
                        <div class="mt-4">
                            <flux:button size="sm" variant="primary" wire:click="install({{ $app->id }})">Install</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
