<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Apps</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Apps</flux:heading>

    @if ($installedApps->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-6 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">No apps installed</flux:heading>
            <flux:text variant="subtle" class="mt-1">Installed apps and integrations will appear here.</flux:text>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($installedApps as $installation)
                <div class="flex items-center gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" wire:key="app-{{ $installation->getKey() }}">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.squares-2x2 class="size-6 text-zinc-500" variant="outline" />
                    </div>
                    <div class="flex-1">
                        <a href="{{ route('admin.apps.show', $installation) }}" class="font-medium underline" wire:navigate>{{ $installation->app?->name ?? 'Unknown app' }}</a>
                        <p class="text-sm text-zinc-500">Installed {{ $installation->installed_at?->diffForHumans() }}</p>
                    </div>
                    <flux:badge :color="$installation->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($installation->status->value) }}</flux:badge>
                    <flux:button variant="ghost" size="sm" wire:click="confirmUninstall({{ $installation->getKey() }})">Uninstall</flux:button>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showUninstallModal" class="max-w-md">
        <flux:heading size="lg">Uninstall this app?</flux:heading>
        <flux:text class="mt-2">The app will lose access to your store data. Its webhook subscriptions will stop receiving events.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showUninstallModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="uninstallApp">Uninstall</flux:button>
        </div>
    </flux:modal>
</div>
