<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="squares-2x2" class="size-6 text-zinc-500 dark:text-zinc-400" />
            </div>
            <div>
                <flux:heading size="xl">{{ $installation->app?->name }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Installed {{ $installation->installed_at?->diffForHumans() ?? 'recently' }}</flux:text>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if ($installation->status === 'active')
                <flux:badge color="green">Active</flux:badge>
            @else
                <flux:badge color="zinc">Inactive</flux:badge>
            @endif
            @if ($installation->status === 'active')
                <flux:button variant="danger" wire:click="uninstallApp" wire:confirm="Uninstall this app? Its webhook subscriptions will stop receiving events.">Uninstall</flux:button>
            @endif
        </div>
    </div>

    {{-- Scopes granted (spec 03 §15) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Scopes granted</flux:heading>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse ($installation->scopes_json ?? [] as $scope)
                <flux:badge color="zinc">{{ $scope }}</flux:badge>
            @empty
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No scopes granted.</flux:text>
            @endforelse
        </div>
    </div>

    {{-- Webhook subscriptions (spec 03 §15) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Webhook subscriptions</flux:heading>

        @if ($webhooks->isEmpty())
            <flux:text class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">This app has no webhook subscriptions.</flux:text>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            <th class="py-2 pr-4">Event type</th>
                            <th class="py-2 pr-4">URL</th>
                            <th class="py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($webhooks as $webhook)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="webhook-{{ $webhook->id }}">
                                <td class="py-2 pr-4 text-zinc-900 dark:text-zinc-100"><code>{{ $webhook->event_type }}</code></td>
                                <td class="max-w-xs truncate py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $webhook->target_url }}</td>
                                <td class="py-2">
                                    @if ($webhook->status === \App\Enums\WebhookSubscriptionStatus::Active)
                                        <flux:badge color="green">Active</flux:badge>
                                    @elseif ($webhook->status === \App\Enums\WebhookSubscriptionStatus::Paused)
                                        <flux:badge color="amber">Paused</flux:badge>
                                    @else
                                        <flux:badge color="zinc">Disabled</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent deliveries --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Recent deliveries</flux:heading>

        @if ($deliveries->isEmpty())
            <flux:text class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No webhook deliveries recorded yet.</flux:text>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Attempts</th>
                            <th class="py-2 pr-4">Response</th>
                            <th class="py-2">Last attempt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveries as $delivery)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="delivery-{{ $delivery->id }}">
                                <td class="py-2 pr-4">
                                    @if ($delivery->status === \App\Enums\WebhookDeliveryStatus::Success)
                                        <flux:badge color="green">Success</flux:badge>
                                    @elseif ($delivery->status === \App\Enums\WebhookDeliveryStatus::Failed)
                                        <flux:badge color="red">Failed</flux:badge>
                                    @else
                                        <flux:badge color="zinc">Pending</flux:badge>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $delivery->attempt_count }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $delivery->response_code ?? '—' }}</td>
                                <td class="py-2 text-zinc-600 dark:text-zinc-300">{{ $delivery->last_attempt_at?->toDayDateTimeString() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
