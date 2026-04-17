<div>
    <div class="mb-6">
        <a href="{{ route('admin.apps.index') }}" wire:navigate class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300">
            &larr; Back to Apps
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-start gap-4 mb-6">
            @if ($installation->app->icon_url)
                <img src="{{ $installation->app->icon_url }}" alt="" class="size-16 rounded-lg">
            @else
                <div class="size-16 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                    <flux:icon name="squares-2x2" class="size-8 text-zinc-400 dark:text-zinc-500" />
                </div>
            @endif
            <div>
                <flux:heading size="xl">{{ $installation->app->name }}</flux:heading>
                @if ($installation->app->developer)
                    <flux:text class="text-zinc-500 dark:text-zinc-400">by {{ $installation->app->developer }}</flux:text>
                @endif
            </div>
        </div>

        @if ($installation->app->description)
            <div class="mb-6">
                <flux:heading size="sm" class="mb-2">Description</flux:heading>
                <flux:text>{{ $installation->app->description }}</flux:text>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <flux:heading size="sm" class="mb-2">Status</flux:heading>
                <flux:badge :variant="$installation->status === 'active' ? 'success' : ($installation->status === 'suspended' ? 'warning' : 'danger')" size="sm">
                    {{ ucfirst($installation->status) }}
                </flux:badge>
            </div>

            <div>
                <flux:heading size="sm" class="mb-2">Installed</flux:heading>
                <flux:text>{{ $installation->installed_at ?? $installation->created_at->format('M d, Y') }}</flux:text>
            </div>

            @if ($installation->scopes_json)
                <div class="sm:col-span-2">
                    <flux:heading size="sm" class="mb-2">Scopes</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ((array) $installation->scopes_json as $scope)
                            <flux:badge variant="outline" size="sm">{{ $scope }}</flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
