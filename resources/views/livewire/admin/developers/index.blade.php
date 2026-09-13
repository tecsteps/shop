<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Developers</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Developers</flux:heading>

    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">API tokens</flux:heading>
        <flux:text variant="subtle" class="mt-1 text-sm">Manage personal access tokens for the Admin API.</flux:text>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2 font-medium">Name</th>
                        <th class="pb-2 font-medium">Last used</th>
                        <th class="pb-2 font-medium">Created</th>
                        <th class="pb-2 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens as $token)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="token-{{ $token->getKey() }}">
                            <td class="py-2">{{ $token->name }}</td>
                            <td class="py-2">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="py-2">{{ $token->created_at?->format('M j, Y') }}</td>
                            <td class="py-2 text-right">
                                <flux:button variant="ghost" size="sm" wire:click="revokeToken({{ $token->getKey() }})">Revoke</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center"><flux:text variant="subtle">No API tokens yet.</flux:text></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <flux:button variant="primary" wire:click="$set('showTokenModal', true)" class="mt-4">Generate new token</flux:button>

        @if ($this->generatedToken !== null)
            <flux:callout variant="warning" icon="exclamation-triangle" heading="Copy this token now. It will not be shown again." class="mt-4">
                <code class="break-all text-xs">{{ $this->generatedToken }}</code>
                <div class="mt-2">
                    <flux:button variant="ghost" size="sm" wire:click="dismissGeneratedToken">Dismiss</flux:button>
                </div>
            </flux:callout>
        @endif
    </div>

    <flux:separator class="my-6" />

    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Webhooks</flux:heading>
        <flux:text variant="subtle" class="mt-1 text-sm">Manage webhook subscriptions for real-time event notifications.</flux:text>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="pb-2 font-medium">Event type</th>
                        <th class="pb-2 font-medium">URL</th>
                        <th class="pb-2 font-medium">Status</th>
                        <th class="pb-2 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($webhooks as $webhook)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="webhook-{{ $webhook->getKey() }}">
                            <td class="py-2"><code class="text-xs">{{ $webhook->event_type }}</code></td>
                            <td class="py-2 break-all">{{ $webhook->target_url }}</td>
                            <td class="py-2">
                                <flux:badge :color="$webhook->status->value === 'active' ? 'green' : ($webhook->status->value === 'paused' ? 'red' : 'zinc')">{{ ucfirst($webhook->status->value) }}</flux:badge>
                            </td>
                            <td class="py-2 text-right">
                                <flux:button variant="ghost" size="sm" wire:click="toggleWebhookStatus({{ $webhook->getKey() }})">{{ $webhook->status->value === 'active' ? 'Pause' : 'Resume' }}</flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="openWebhookModal({{ $webhook->getKey() }})" aria-label="Edit webhook">
                                    <flux:icon.pencil class="size-4" variant="outline" />
                                </flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="deleteWebhook({{ $webhook->getKey() }})" aria-label="Delete webhook">
                                    <flux:icon.trash class="size-4" variant="outline" />
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center"><flux:text variant="subtle">No webhook subscriptions yet.</flux:text></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <flux:button variant="primary" wire:click="openWebhookModal()" class="mt-4">Add webhook</flux:button>
    </div>

    <flux:modal wire:model="showTokenModal" class="max-w-md">
        <flux:heading size="lg">Generate API token</flux:heading>
        <flux:input wire:model="newTokenName" label="Token name" placeholder="My integration" class="mt-4" />
        <p class="mt-3 text-sm font-medium">Abilities</p>
        <div class="mt-2 grid max-h-56 grid-cols-2 gap-2 overflow-y-auto">
            @foreach ($abilities as $ability)
                <label class="flex items-center gap-2 text-sm" wire:key="ability-{{ $ability }}">
                    <flux:checkbox wire:model="newTokenAbilities" value="{{ $ability }}" />
                    <span class="text-xs">{{ $ability }}</span>
                </label>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showTokenModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="generateToken">Generate</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showWebhookModal" class="max-w-md">
        <flux:heading size="lg">{{ $this->editingWebhookId ? 'Edit webhook' : 'Add webhook' }}</flux:heading>
        <flux:select wire:model="webhookEventType" label="Event type" class="mt-4">
            @foreach ($eventTypes as $eventType)
                <flux:select.option value="{{ $eventType }}">{{ $eventType }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="webhookUrl" label="Endpoint URL" type="url" placeholder="https://example.com/webhooks" class="mt-3" />
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showWebhookModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveWebhook">Save</flux:button>
        </div>
    </flux:modal>
</div>
