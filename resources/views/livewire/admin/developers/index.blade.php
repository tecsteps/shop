<div class="space-y-6 p-6">
    <flux:heading size="xl">Developers</flux:heading>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">API tokens</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">Personal access tokens for the Admin API.</p>

        <form wire:submit="createToken" class="mt-4 flex items-end gap-3">
            <flux:field class="flex-1">
                <flux:label>Token name</flux:label>
                <flux:input wire:model="newTokenName" placeholder="My integration" />
                <flux:error name="newTokenName" />
            </flux:field>
            <flux:button type="submit" variant="primary" icon="plus">Create token</flux:button>
        </form>

        @if ($plaintextToken !== null)
            <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-700 dark:bg-amber-950">
                <p class="font-medium text-amber-800 dark:text-amber-200">Copy this token now. It will not be shown again.</p>
                <code class="mt-2 block break-all rounded bg-white p-2 font-mono text-xs dark:bg-zinc-900">{{ $plaintextToken }}</code>
            </div>
        @endif

        <div class="mt-4">
            @if ($tokens->isEmpty())
                <p class="text-sm text-zinc-500">No tokens yet.</p>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Created</flux:table.column>
                        <flux:table.column>Last used</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($tokens as $token)
                            <flux:table.row>
                                <flux:table.cell>{{ $token->name }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $token->created_at?->diffForHumans() }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $token->last_used_at?->diffForHumans() ?? 'never' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button size="xs" variant="danger" wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke this token?">Revoke</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Webhook subscriptions</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">HTTP endpoints notified when events occur.</p>

        <form wire:submit="createWebhook" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
            <flux:field>
                <flux:label>Event type</flux:label>
                <flux:input wire:model="webhookEventType" placeholder="order.placed" />
                <flux:error name="webhookEventType" />
            </flux:field>
            <flux:field class="md:col-span-2">
                <flux:label>URL</flux:label>
                <flux:input wire:model="webhookUrl" placeholder="https://example.com/webhook" />
                <flux:error name="webhookUrl" />
            </flux:field>
            <div class="md:col-span-3 flex justify-end">
                <flux:button type="submit" variant="primary" icon="plus">Add webhook</flux:button>
            </div>
        </form>

        <div class="mt-4">
            @if ($webhooks->isEmpty())
                <p class="text-sm text-zinc-500">No webhooks yet.</p>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Event</flux:table.column>
                        <flux:table.column>URL</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($webhooks as $webhook)
                            <flux:table.row>
                                <flux:table.cell>{{ $webhook->event_type }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $webhook->url }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$webhook->status === 'active' ? 'green' : 'zinc'">{{ $webhook->status }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button size="xs" variant="danger" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="Delete webhook?">Delete</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </div>
    </div>
</div>
