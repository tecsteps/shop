<div>
    <flux:heading size="xl" class="mb-6">Apps</flux:heading>

    @if ($installations->isEmpty())
        <div class="text-center py-16">
            <flux:icon name="squares-2x2" class="size-12 mx-auto text-zinc-400 dark:text-zinc-500 mb-4" />
            <flux:heading size="lg">No apps installed</flux:heading>
            <flux:text class="mt-2">Install apps to extend your store functionality.</flux:text>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($installations as $installation)
                <a
                    href="{{ route('admin.apps.show', $installation->id) }}"
                    wire:navigate
                    wire:key="app-{{ $installation->id }}"
                    class="block bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 hover:border-zinc-300 dark:hover:border-zinc-600 transition-colors"
                >
                    <div class="flex items-start gap-4">
                        @if ($installation->app->icon_url)
                            <img src="{{ $installation->app->icon_url }}" alt="" class="size-12 rounded-lg">
                        @else
                            <div class="size-12 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                <flux:icon name="squares-2x2" class="size-6 text-zinc-400 dark:text-zinc-500" />
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <flux:heading size="base">{{ $installation->app->name }}</flux:heading>
                            @if ($installation->app->developer)
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">by {{ $installation->app->developer }}</flux:text>
                            @endif
                            <div class="mt-2">
                                <flux:badge :variant="$installation->status === 'active' ? 'success' : ($installation->status === 'suspended' ? 'warning' : 'danger')" size="sm">
                                    {{ ucfirst($installation->status) }}
                                </flux:badge>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
