<div>
    <flux:heading size="xl">Developers</flux:heading>

    <div class="mt-6 max-w-4xl space-y-8">
        {{-- API tokens --}}
        <section>
            <flux:heading size="lg">API tokens</flux:heading>
            <flux:text class="mt-1">Manage personal access tokens for the Admin API.</flux:text>

            @if ($generatedToken)
                <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                    <b>Copy this token now. It will not be shown again.</b>
                    <div class="mt-2 rounded-lg bg-white/10 px-3 py-2 font-mono text-xs break-all dark:bg-zinc-950/40">
                        {{ $generatedToken }}
                    </div>
                </flux:callout>
            @endif

            <flux:card class="mt-4 overflow-hidden">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Last used</flux:table.column>
                        <flux:table.column>Created</flux:table.column>
                        <flux:table.column class="text-end">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->tokens as $token)
                            <flux:table.row :key="$token->id">
                                <flux:table.cell variant="strong">{{ $token->name }}</flux:table.cell>
                                <flux:table.cell>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</flux:table.cell>
                                <flux:table.cell>{{ $token->created_at?->format('M j, Y') }}</flux:table.cell>
                                <flux:table.cell class="text-end">
                                    <flux:button variant="danger" size="sm" wire:click="revokeToken({{ $token->id }})">
                                        Revoke
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" align="center" class="py-10 text-zinc-400">
                                    No API tokens yet.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            <flux:button variant="primary" icon="plus" wire:click="$set('showGenerateToken', true)" class="mt-3">
                Generate new token
            </flux:button>
        </section>

        <flux:separator />

        {{-- Webhooks --}}
        <section>
            <flux:heading size="lg">Webhooks</flux:heading>
            <flux:text class="mt-1">Manage webhook subscriptions for real-time event notifications.</flux:text>

            <flux:card class="mt-4 overflow-hidden">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Event type</flux:table.column>
                        <flux:table.column>URL</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column class="text-end">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->webhooks as $webhook)
                            <flux:table.row :key="$webhook->id">
                                <flux:table.cell>
                                    <span class="font-mono text-xs">{{ $webhook->event_type }}</span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-xs truncate">{{ $webhook->target_url }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$webhook->status === 'active' ? 'green' : 'red'" size="sm">
                                        {{ ucfirst($webhook->status) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-end">
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="openWebhookModal({{ $webhook->id }})" aria-label="Edit webhook" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteWebhook({{ $webhook->id }})" aria-label="Delete webhook" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" align="center" class="py-10 text-zinc-400">
                                    No webhook subscriptions yet.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            <flux:button variant="ghost" size="sm" icon="plus" wire:click="openWebhookModal" class="mt-3">
                Add webhook
            </flux:button>
        </section>
    </div>

    {{-- Generate token modal --}}
    <flux:modal wire:model="showGenerateToken" class="max-w-md">
        <flux:heading size="lg">Generate API token</flux:heading>

        <div class="mt-4">
            <flux:field>
                <flux:label>Token name</flux:label>
                <flux:input wire:model="newTokenName" placeholder="My integration" />
                <flux:error name="newTokenName" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showGenerateToken', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="generateToken">Generate</flux:button>
        </div>
    </flux:modal>

    {{-- Webhook modal --}}
    <flux:modal wire:model="showWebhookModal" class="max-w-md">
        <flux:heading size="lg">{{ $editingWebhookId ? 'Edit webhook' : 'Add webhook' }}</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:field>
                <flux:label>Event type</flux:label>
                <flux:select wire:model="webhookEventType">
                    @foreach ($this->eventTypes() as $eventType)
                        <option value="{{ $eventType }}">{{ $eventType }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="webhookEventType" />
            </flux:field>
            <flux:field>
                <flux:label>Endpoint URL</flux:label>
                <flux:input type="url" wire:model="webhookUrl" placeholder="https://example.com/webhooks" />
                <flux:error name="webhookUrl" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showWebhookModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveWebhook">Save</flux:button>
        </div>
    </flux:modal>
</div>
