<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Apps</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Installed apps</flux:heading>

    @if ($installations->count() > 0)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($installations as $installation)
                <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden" wire:key="app-{{ $installation->id }}">
                    <div class="p-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                <flux:icon name="squares-2x2" variant="outline" class="size-5 text-zinc-500" />
                            </div>
                            <div class="min-w-0">
                                <flux:heading size="base">{{ $installation->app->name ?? 'Unknown App' }}</flux:heading>
                                @if ($installation->app?->developer_name)
                                    <flux:text class="text-xs text-zinc-500">by {{ $installation->app->developer_name }}</flux:text>
                                @endif
                            </div>
                        </div>

                        @if ($installation->app?->description)
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">
                                {{ $installation->app->description }}
                            </flux:text>
                        @endif

                        <div class="flex items-center justify-between">
                            <flux:badge size="sm" :color="$installation->status === 'active' ? 'green' : 'zinc'">
                                {{ ucfirst($installation->status ?? 'installed') }}
                            </flux:badge>

                            <div class="flex items-center gap-2">
                                <flux:button href="{{ route('admin.apps.show', $installation) }}" wire:navigate size="sm" variant="ghost">Details</flux:button>
                                <flux:button wire:click="uninstall({{ $installation->id }})" wire:confirm="Uninstall this app?" size="sm" variant="ghost" icon="trash" />
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="squares-2x2" variant="outline" class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="md" class="mt-4">No apps installed</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Install apps to extend your store's functionality.</flux:text>
        </div>
    @endif
</div>
