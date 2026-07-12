<div class="space-y-6">
    <x-admin.page-header :title="$installation->app->name" description="Review permissions, webhooks, and integration activity.">
        <x-slot:actions>
            @php($status = $installation->status instanceof BackedEnum ? $installation->status->value : $installation->status)
            @if($status === 'active')
                <flux:button type="button" wire:click="suspend">Suspend</flux:button>
            @elseif($status === 'suspended')
                <flux:button type="button" wire:click="resume" variant="primary">Resume</flux:button>
            @endif
            <flux:button type="button" wire:click="uninstall" wire:confirm="Uninstall this app?" variant="danger">Uninstall</flux:button>
        </x-slot:actions>
    </x-admin.page-header>
    <div class="grid gap-6 lg:grid-cols-3"><x-admin.card title="Status"><x-admin.status-badge :status="$installation->status" /><p class="mt-3 text-sm text-zinc-500">Installed {{ $installation->installed_at?->diffForHumans() ?? 'recently' }}</p></x-admin.card><x-admin.card title="API activity"><p class="text-3xl font-semibold">{{ number_format($this->usage['calls']) }}</p><p class="mt-1 text-sm text-zinc-500">Recorded webhook calls</p></x-admin.card><x-admin.card title="Last activity"><p class="font-semibold">{{ $this->usage['last_call_at'] ? Illuminate\Support\Carbon::parse($this->usage['last_call_at'])->diffForHumans() : 'Never' }}</p></x-admin.card></div>
    <x-admin.card title="Granted scopes"><div class="flex flex-wrap gap-2">@forelse((array)$installation->scopes_json as $scope)<x-admin.status-badge status="active" :label="$scope" :show-dot="false" />@empty<p class="text-sm text-zinc-500">No scopes granted.</p>@endforelse</div></x-admin.card>
    <x-admin.card title="Webhook subscriptions">
        <x-admin.table-shell caption="App webhooks">
            <x-slot:head><tr><th>Event</th><th>URL</th><th>Status</th><th>Deliveries</th></tr></x-slot:head>
            @forelse($this->webhooks as $webhook)
                <tr><td class="font-mono text-xs">{{ $webhook->event_type }}</td><td class="max-w-md truncate">{{ $webhook->target_url }}</td><td><x-admin.status-badge :status="$webhook->status" /></td><td>{{ $webhook->deliveries->count() }}</td></tr>
            @empty
                <x-admin.table-empty colspan="4" title="No webhook subscriptions" />
            @endforelse
        </x-admin.table-shell>
    </x-admin.card>
</div>
