<div class="space-y-8">
    <x-admin.page-header title="Developers" description="Manage Admin API tokens and outgoing webhooks." />
    @if($generatedToken)<div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950"><h2 class="font-semibold">Copy this token now</h2><p class="mt-1 text-sm">It will not be shown again.</p><code class="mt-3 block break-all rounded-lg bg-white p-3 text-xs select-all">{{ $generatedToken }}</code></div>@endif
    <x-admin.card title="API tokens" description="Personal access tokens are limited to this store's Admin API.">
        <x-slot:actions><flux:modal.trigger name="generate-token"><flux:button type="button" icon="plus">Generate new token</flux:button></flux:modal.trigger></x-slot:actions>
        <x-admin.table-shell caption="API tokens">
            <x-slot:head><tr><th>Name</th><th>Last used</th><th>Created</th><th class="text-right">Actions</th></tr></x-slot:head>
            @forelse($this->tokens as $token)
                <tr><td class="font-medium">{{ Str::after($token->name, 'store:'.$adminStore->id.':') }}</td><td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $token->created_at->format('M j, Y') }}</td><td class="text-right"><flux:button type="button" wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke this token?" variant="danger" size="sm">Revoke</flux:button></td></tr>
            @empty
                <x-admin.table-empty colspan="4" title="No API tokens" />
            @endforelse
        </x-admin.table-shell>
    </x-admin.card>
    <x-admin.card title="Webhooks" description="Send store events to secure HTTPS endpoints.">
        <x-slot:actions><flux:modal.trigger name="webhook-form"><flux:button type="button" wire:click="openWebhookModal" icon="plus">Add webhook</flux:button></flux:modal.trigger></x-slot:actions>
        <x-admin.table-shell caption="Webhooks">
            <x-slot:head><tr><th>Event type</th><th>Endpoint</th><th>Status</th><th>Deliveries</th><th class="text-right">Actions</th></tr></x-slot:head>
            @forelse($this->webhooks as $webhook)
                <tr><td class="font-mono text-xs">{{ $webhook->event_type }}</td><td class="max-w-md truncate">{{ $webhook->target_url }}</td><td><x-admin.status-badge :status="$webhook->status" /></td><td>{{ $webhook->deliveries->count() }}</td><td><div class="flex justify-end gap-1"><flux:modal.trigger name="webhook-form"><flux:button type="button" wire:click="openWebhookModal({{ $webhook->id }})" variant="ghost" size="sm" icon="pencil" aria-label="Edit webhook" /></flux:modal.trigger><flux:button type="button" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="Delete this webhook?" variant="ghost" size="sm" icon="trash" aria-label="Delete webhook" /></div></td></tr>
            @empty
                <x-admin.table-empty colspan="5" title="No webhooks configured" />
            @endforelse
        </x-admin.table-shell>
    </x-admin.card>
    <flux:modal name="generate-token" class="max-w-md"><form wire:submit="generateToken" class="space-y-6"><flux:heading size="lg">Generate API token</flux:heading><flux:input wire:model="newTokenName" label="Token name" placeholder="My integration" required /><div class="flex justify-end gap-2"><flux:modal.close><flux:button type="button" variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Generate</flux:button></div></form></flux:modal>
    <flux:modal name="webhook-form" class="max-w-md"><form wire:submit="saveWebhook" class="space-y-6"><flux:heading size="lg">{{ $editingWebhook ? 'Edit webhook' : 'Add webhook' }}</flux:heading><flux:select wire:model="webhookEventType" label="Event type">@foreach($this->eventTypes() as $eventType)<flux:select.option :value="$eventType">{{ $eventType }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="webhookUrl" type="url" label="Endpoint URL" placeholder="https://example.com/webhooks" required /><div class="flex justify-end gap-2"><flux:modal.close><flux:button type="button" variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Save</flux:button></div></form></flux:modal>
</div>
