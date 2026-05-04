<section class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Developers</flux:heading>
            <flux:text class="mt-1">API access and outbound webhook subscriptions.</flux:text>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">
            {{ session('status') }}
        </flux:callout>
    @endif

    @if ($generatedToken)
        <flux:callout color="amber" icon="key">
            <div class="space-y-2">
                <div>Copy this token now. It will not be shown again.</div>
                <code class="block overflow-x-auto rounded-md bg-zinc-950 px-3 py-2 text-sm text-white">{{ $generatedToken }}</code>
            </div>
        </flux:callout>
    @endif

    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="lg">API tokens</flux:heading>
                <flux:text class="mt-1">Personal access tokens for store integrations.</flux:text>
            </div>

            <flux:modal.trigger name="generate-token">
                <flux:button icon="plus" variant="primary">Generate new token</flux:button>
            </flux:modal.trigger>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">App</th>
                        <th class="px-4 py-3">Last used</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($tokens as $token)
                        <tr wire:key="developer-token-{{ $token->getKey() }}">
                            <td class="px-4 py-3 font-medium">{{ $token->name ?? 'API token' }}</td>
                            <td class="px-4 py-3">{{ $token->installation->app->name }}</td>
                            <td class="px-4 py-3">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-4 py-3">{{ $token->created_at?->toFormattedDateString() ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:button wire:click="revokeToken({{ $token->getKey() }})" wire:confirm="Revoke this token?" size="sm" variant="danger">
                                    Revoke
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No API tokens created.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <flux:separator />

    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="lg">Webhooks</flux:heading>
                <flux:text class="mt-1">Send real-time event notifications to external endpoints.</flux:text>
            </div>

            <flux:button wire:click="openWebhookModal" icon="plus" variant="primary">
                Add webhook
            </flux:button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Event type</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($webhooks as $webhook)
                        <tr wire:key="developer-webhook-{{ $webhook->getKey() }}">
                            <td class="px-4 py-3 font-medium">{{ $webhook->event_type->value }}</td>
                            <td class="max-w-lg truncate px-4 py-3">{{ $webhook->target_url }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$webhook->status->badgeColor()">{{ $webhook->status->label() }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button wire:click="openWebhookModal({{ $webhook->getKey() }})" size="sm" variant="filled">Edit</flux:button>
                                    <flux:button wire:click="deleteWebhook({{ $webhook->getKey() }})" wire:confirm="Delete this webhook?" size="sm" variant="danger">Delete</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-zinc-500">No webhook subscriptions created.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <flux:modal name="generate-token" class="md:w-[32rem]">
        <form wire:submit="generateToken" class="space-y-6">
            <flux:heading size="lg">Generate API token</flux:heading>

            <flux:input wire:model="newTokenName" label="Token name" placeholder="My integration" />
            <flux:error name="newTokenName" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Generate</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="webhook-form" class="md:w-[34rem]">
        <form wire:submit="saveWebhook" class="space-y-6">
            <flux:heading size="lg">{{ $editingWebhookId ? 'Edit webhook' : 'Add webhook' }}</flux:heading>

            <flux:select wire:model="webhookEventType" label="Event type">
                @foreach ($eventTypes as $eventType)
                    <flux:select.option wire:key="event-type-{{ $eventType->value }}" value="{{ $eventType->value }}">
                        {{ $eventType->value }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="webhookEventType" />

            <flux:input type="url" wire:model="webhookUrl" label="Endpoint URL" placeholder="https://example.com/webhooks" />
            <flux:error name="webhookUrl" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
