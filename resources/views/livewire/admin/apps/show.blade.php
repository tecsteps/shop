<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.apps.index') }}" wire:navigate>Apps</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $installation->app?->name ?? 'App' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <flux:heading size="xl">{{ $installation->app?->name ?? 'Unknown app' }}</flux:heading>
        <flux:badge :color="$installation->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($installation->status->value) }}</flux:badge>
    </div>
    <flux:text variant="subtle" class="mb-6 -mt-4">Installed {{ $installation->installed_at?->format('M j, Y g:i A') }}</flux:text>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Scopes granted</flux:heading>
            @php $scopes = is_array($installation->scopes_json) ? $installation->scopes_json : []; @endphp
            @if ($scopes === [])
                <flux:text variant="subtle" class="mt-3">No scopes granted.</flux:text>
            @else
                <ul class="mt-3 flex flex-col gap-1">
                    @foreach ($scopes as $scope)
                        <li class="text-sm"><code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">{{ $scope }}</code></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Usage</flux:heading>
            <div class="mt-3 flex flex-col gap-1 text-sm">
                <p>Webhook deliveries: {{ number_format($deliveryStats['count']) }}</p>
                <p>Last delivery: {{ $deliveryStats['last_at'] ? \Carbon\Carbon::parse($deliveryStats['last_at'])->diffForHumans() : 'Never' }}</p>
                <p>OAuth tokens: {{ $installation->oauthTokens->count() }}</p>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">Webhook subscriptions</flux:heading>
        @if ($installation->webhookSubscriptions->isEmpty())
            <flux:text variant="subtle" class="mt-3">No webhook subscriptions for this app.</flux:text>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium">Event type</th>
                            <th class="pb-2 font-medium">URL</th>
                            <th class="pb-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($installation->webhookSubscriptions as $subscription)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="sub-{{ $subscription->getKey() }}">
                                <td class="py-2"><code class="text-xs">{{ $subscription->event_type }}</code></td>
                                <td class="py-2 break-all">{{ $subscription->target_url }}</td>
                                <td class="py-2"><flux:badge :color="$subscription->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($subscription->status->value) }}</flux:badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
