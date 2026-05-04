<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $installation->app->name }}</flux:heading>
            <flux:text class="mt-1">Installed {{ $installation->installed_at?->toDayDateTimeString() ?? 'recently' }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:badge :color="$installation->status->badgeColor()">{{ $installation->status->label() }}</flux:badge>
            <flux:button wire:click="uninstallApp" wire:confirm="Uninstall this app?" variant="danger">
                Uninstall
            </flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">
            {{ session('status') }}
        </flux:callout>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.45fr)]">
        <div class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Webhook subscriptions</flux:heading>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            <tr>
                                <th class="py-3 pr-4">Event</th>
                                <th class="px-4 py-3">URL</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="py-3 pl-4 text-right">Deliveries</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($installation->webhookSubscriptions as $webhook)
                                <tr wire:key="app-webhook-{{ $webhook->getKey() }}">
                                    <td class="py-3 pr-4 font-medium">{{ $webhook->event_type->value }}</td>
                                    <td class="max-w-md truncate px-4 py-3">{{ $webhook->target_url }}</td>
                                    <td class="px-4 py-3">
                                        <flux:badge :color="$webhook->status->badgeColor()">{{ $webhook->status->label() }}</flux:badge>
                                    </td>
                                    <td class="py-3 pl-4 text-right">{{ $webhook->deliveries->count() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-zinc-500">No webhooks registered for this app.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">API usage</flux:heading>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            <tr>
                                <th class="py-3 pr-4">Token</th>
                                <th class="px-4 py-3">Last used</th>
                                <th class="py-3 pl-4 text-right">Expires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($installation->oauthTokens as $token)
                                <tr wire:key="app-token-{{ $token->getKey() }}">
                                    <td class="py-3 pr-4 font-medium">{{ $token->name ?? 'API token' }}</td>
                                    <td class="px-4 py-3">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td class="py-3 pl-4 text-right">{{ $token->expires_at->toFormattedDateString() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-10 text-center text-zinc-500">No tokens issued.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Scopes</flux:heading>

            <div class="mt-5 flex flex-wrap gap-2">
                @forelse ($installation->scopes_json ?? [] as $scope)
                    <flux:badge wire:key="show-scope-{{ $scope }}">{{ $scope }}</flux:badge>
                @empty
                    <flux:text>No scopes granted.</flux:text>
                @endforelse
            </div>
        </aside>
    </div>
</section>
