<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Apps</flux:heading>
            <flux:text class="mt-1">Installed integrations and their store permissions.</flux:text>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">
            {{ session('status') }}
        </flux:callout>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($installedApps as $installation)
            <article wire:key="app-installation-{{ $installation->getKey() }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <a href="{{ route('admin.apps.show', $installation) }}" wire:navigate class="flex min-w-0 items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                            <flux:icon name="squares-2x2" class="size-6" />
                        </div>

                        <div class="min-w-0">
                            <flux:heading size="lg" class="truncate">{{ $installation->app->name }}</flux:heading>
                            <flux:text class="mt-1">
                                Installed {{ $installation->installed_at?->diffForHumans() ?? 'recently' }}
                            </flux:text>
                        </div>
                    </a>

                    <flux:badge :color="$installation->status->badgeColor()">
                        {{ $installation->status->label() }}
                    </flux:badge>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($installation->scopes_json ?? [] as $scope)
                        <flux:badge wire:key="app-{{ $installation->getKey() }}-scope-{{ $scope }}">{{ $scope }}</flux:badge>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center justify-between gap-3">
                    <flux:text>{{ $installation->webhook_subscriptions_count ?? $installation->webhookSubscriptions->count() }} webhooks</flux:text>

                    <div class="flex gap-2">
                        <flux:button :href="route('admin.apps.show', $installation)" wire:navigate size="sm" variant="filled">
                            View
                        </flux:button>
                        <flux:button wire:click="uninstallApp({{ $installation->app_id }})" wire:confirm="Uninstall this app?" size="sm" variant="danger">
                            Uninstall
                        </flux:button>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-12 text-center dark:border-zinc-700 lg:col-span-2">
                <flux:icon name="squares-2x2" class="mx-auto size-9 text-zinc-400" />
                <flux:heading size="lg" class="mt-3">No apps installed</flux:heading>
                <flux:text class="mt-1">Installed integrations will appear here.</flux:text>
            </div>
        @endforelse
    </div>
</section>
