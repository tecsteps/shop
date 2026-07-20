<div class="space-y-6">
    <flux:heading size="xl">Developers</flux:heading>

    {{-- API tokens (spec 03 §16) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">API tokens</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage personal access tokens for the Admin API.</flux:text>

        @if ($generatedToken !== null)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                <flux:callout.heading>Copy this token now. It will not be shown again.</flux:callout.heading>
                <flux:callout.text>
                    <div class="mt-2 flex items-center gap-2">
                        <code class="flex-1 break-all rounded bg-zinc-100 px-2 py-1 text-sm dark:bg-zinc-800">{{ $generatedToken }}</code>
                        <flux:button variant="ghost" icon="clipboard" x-data @click="navigator.clipboard.writeText(@js($generatedToken))">Copy</flux:button>
                    </div>
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Abilities</th>
                        <th class="py-2 pr-4">Last used</th>
                        <th class="py-2 pr-4">Expires</th>
                        <th class="py-2 pr-4">Created</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens as $token)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="token-{{ $token->id }}">
                            <td class="py-2 pr-4 text-zinc-900 dark:text-zinc-100">{{ $token->name }}</td>
                            <td class="py-2 pr-4">
                                <div class="flex max-w-xs flex-wrap gap-1">
                                    @foreach ($token->abilities ?? [] as $ability)
                                        <flux:badge size="sm" color="zinc">{{ $ability }}</flux:badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $token->expires_at?->toFormattedDateString() ?? 'Never' }}</td>
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $token->created_at?->toFormattedDateString() }}</td>
                            <td class="py-2">
                                <flux:button variant="danger" size="sm" wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke this token? API clients using it will lose access immediately.">Revoke</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No API tokens yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <flux:button variant="primary" wire:click="$set('showTokenModal', true)">Generate new token</flux:button>
        </div>
    </div>

    <flux:separator />

    {{-- Webhooks (spec 03 §16) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Webhooks</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage webhook subscriptions for real-time event notifications.</flux:text>

        @if ($generatedWebhookSecret !== null)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                <flux:callout.heading>Copy this signing secret now. It will not be shown again.</flux:callout.heading>
                <flux:callout.text>
                    <div class="mt-2 flex items-center gap-2">
                        <code class="flex-1 break-all rounded bg-zinc-100 px-2 py-1 text-sm dark:bg-zinc-800">{{ $generatedWebhookSecret }}</code>
                        <flux:button variant="ghost" icon="clipboard" x-data @click="navigator.clipboard.writeText(@js($generatedWebhookSecret))">Copy</flux:button>
                    </div>
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        <th class="py-2 pr-4">Event type</th>
                        <th class="py-2 pr-4">URL</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($webhooks as $webhook)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="webhook-{{ $webhook->id }}">
                            <td class="py-2 pr-4 text-zinc-900 dark:text-zinc-100"><code>{{ $webhook->event_type }}</code></td>
                            <td class="max-w-xs truncate py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $webhook->target_url }}</td>
                            <td class="py-2 pr-4">
                                @if ($webhook->status === \App\Enums\WebhookSubscriptionStatus::Active && $webhook->consecutiveFailures() > 0)
                                    <flux:badge color="red">Failing</flux:badge>
                                @elseif ($webhook->status === \App\Enums\WebhookSubscriptionStatus::Active)
                                    <flux:badge color="green">Active</flux:badge>
                                @elseif ($webhook->status === \App\Enums\WebhookSubscriptionStatus::Paused)
                                    <flux:badge color="amber">Paused</flux:badge>
                                @else
                                    <flux:badge color="zinc">Disabled</flux:badge>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-1">
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="openWebhookModal({{ $webhook->id }})" aria-label="Edit webhook" />
                                    @if ($webhook->status === \App\Enums\WebhookSubscriptionStatus::Active)
                                        <flux:button variant="ghost" size="sm" icon="pause" wire:click="pauseWebhook({{ $webhook->id }})" aria-label="Pause webhook" />
                                    @else
                                        <flux:button variant="ghost" size="sm" icon="play" wire:click="resumeWebhook({{ $webhook->id }})" aria-label="Resume webhook" />
                                    @endif
                                    <flux:button variant="ghost" size="sm" icon="queue-list" wire:click="viewDeliveries({{ $webhook->id }})" aria-label="View deliveries" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="Delete this webhook subscription?" aria-label="Delete webhook" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No webhooks configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <flux:button variant="primary" icon="plus" wire:click="openWebhookModal">Add webhook</flux:button>
        </div>
    </div>

    {{-- Generate token modal (spec 03 §16) --}}
    <flux:modal wire:model="showTokenModal" name="generate-token" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Generate API token</flux:heading>

            <flux:field>
                <flux:label>Token name</flux:label>
                <flux:input wire:model.blur="newTokenName" placeholder="My integration" />
                <flux:error name="newTokenName" />
            </flux:field>

            <flux:field>
                <flux:label>Abilities</flux:label>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($abilities as $ability => $label)
                        <flux:checkbox wire:model.blur="newTokenAbilities" value="{{ $ability }}" :label="$label" wire:key="ability-{{ $ability }}" />
                    @endforeach
                </div>
                <flux:error name="newTokenAbilities" />
            </flux:field>

            <flux:field>
                <flux:label>Expires at (optional)</flux:label>
                <flux:input type="date" wire:model.blur="newTokenExpiresAt" />
                <flux:description>Defaults to one year from now.</flux:description>
                <flux:error name="newTokenExpiresAt" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showTokenModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="generateToken">Generate</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Webhook create/edit modal (spec 03 §16) --}}
    <flux:modal wire:model="showWebhookModal" name="webhook-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingWebhookId !== null ? 'Edit webhook' : 'Add webhook' }}</flux:heading>

            <flux:field>
                <flux:label>Event type</flux:label>
                <flux:select wire:model.blur="webhookEventType">
                    @foreach ($eventTypes as $eventType)
                        <flux:select.option value="{{ $eventType }}">{{ $eventType }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="webhookEventType" />
            </flux:field>

            <flux:field>
                <flux:label>Endpoint URL</flux:label>
                <flux:input type="url" wire:model.blur="webhookUrl" placeholder="https://example.com/webhooks" />
                <flux:error name="webhookUrl" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showWebhookModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveWebhook">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Deliveries log modal (spec 03 §16) --}}
    <flux:modal wire:model="showDeliveriesModal" name="webhook-deliveries" class="max-w-2xl">
        <div class="space-y-4">
            <flux:heading size="lg">Recent deliveries</flux:heading>

            @if ($deliveries->isEmpty())
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No deliveries recorded yet.</flux:text>
            @else
                <div class="overflow-x-auto">
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
    </flux:modal>
</div>
