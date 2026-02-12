<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.apps.index') }}" wire:navigate>Apps</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $installation->app->name ?? 'App' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="max-w-2xl space-y-6">
        {{-- App header --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start gap-4">
                <div class="size-16 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                    <flux:icon name="squares-2x2" variant="outline" class="size-8 text-zinc-500" />
                </div>
                <div class="min-w-0 flex-1">
                    <flux:heading size="xl">{{ $installation->app->name ?? 'Unknown App' }}</flux:heading>
                    @if ($installation->app?->developer_name)
                        <flux:text class="text-zinc-500">by {{ $installation->app->developer_name }}</flux:text>
                    @endif
                    @if ($installation->app?->description)
                        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $installation->app->description }}</flux:text>
                    @endif
                </div>
            </div>
        </div>

        {{-- Installation details --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">Installation details</flux:heading>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <flux:text class="text-zinc-500">Status</flux:text>
                    <div class="mt-1">
                        <flux:badge size="sm" :color="$installation->status === 'active' ? 'green' : 'zinc'">
                            {{ ucfirst($installation->status ?? 'installed') }}
                        </flux:badge>
                    </div>
                </div>

                <div>
                    <flux:text class="text-zinc-500">Installed at</flux:text>
                    <flux:text class="mt-1 text-zinc-900 dark:text-zinc-100">
                        {{ $installation->installed_at?->format('M d, Y H:i') ?? $installation->created_at->format('M d, Y H:i') }}
                    </flux:text>
                </div>

                @if ($installation->app?->handle)
                    <div>
                        <flux:text class="text-zinc-500">Handle</flux:text>
                        <flux:text class="mt-1 font-mono text-xs text-zinc-900 dark:text-zinc-100">{{ $installation->app->handle }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        {{-- Scopes --}}
        @if ($installation->granted_scopes_json && count($installation->granted_scopes_json) > 0)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <flux:heading size="lg">Granted permissions</flux:heading>
                <div class="flex flex-wrap gap-2">
                    @foreach ($installation->granted_scopes_json as $scope)
                        <flux:badge size="sm" color="zinc">{{ $scope }}</flux:badge>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex items-center gap-4">
            <flux:button wire:click="uninstall" wire:confirm="Are you sure you want to uninstall this app?" variant="danger">Uninstall app</flux:button>
            <flux:button href="{{ route('admin.apps.index') }}" wire:navigate variant="ghost">Back to apps</flux:button>
        </div>
    </div>
</div>
