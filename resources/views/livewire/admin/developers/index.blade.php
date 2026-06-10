<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Developers')]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Developers') }}</flux:heading>

        <flux:modal.trigger name="generate-token">
            <flux:button variant="primary" icon="plus" data-test="generate-token-button">
                {{ __('Generate new token') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <div>
        <flux:heading size="lg">{{ __('API tokens') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Manage personal access tokens for the Admin API. Tokens are sent as a Bearer header and expire after one year.') }}</flux:text>
    </div>

    @if ($generatedToken !== null)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/40" data-test="generated-token-callout">
            <div class="flex items-start gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                <div class="min-w-0 space-y-2">
                    <flux:text class="font-medium text-amber-900 dark:text-amber-200">
                        {{ __('Copy this token now. It will not be shown again.') }}
                    </flux:text>
                    <code class="block overflow-x-auto rounded-lg bg-amber-100 px-3 py-2 font-mono text-sm text-amber-900 dark:bg-amber-900/40 dark:text-amber-100" data-test="generated-token-value">{{ $generatedToken }}</code>
                </div>
            </div>
        </div>
    @endif

    <x-admin.card class="!p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-2.5">{{ __('Name') }}</th>
                        <th class="px-4 py-2.5">{{ __('Abilities') }}</th>
                        <th class="px-4 py-2.5">{{ __('Last used') }}</th>
                        <th class="px-4 py-2.5">{{ __('Created') }}</th>
                        <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->tokens as $token)
                        <tr wire:key="token-{{ $token->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $token->name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex max-w-md flex-wrap gap-1">
                                    @foreach ($token->abilities ?? [] as $ability)
                                        <flux:badge size="sm" color="zinc">{{ $ability }}</flux:badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $token->last_used_at?->diffForHumans() ?? __('Never') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $token->created_at?->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button
                                    size="sm"
                                    variant="danger"
                                    wire:click="revokeToken({{ $token->id }})"
                                    wire:confirm="{{ __('Revoke this token? Applications using it will immediately lose access.') }}"
                                    data-test="revoke-token-{{ $token->id }}"
                                >
                                    {{ __('Revoke') }}
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <flux:text>{{ __('No API tokens yet. Generate one to access the Admin API.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <flux:separator />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ __('Webhooks') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Manage webhook subscriptions for real-time event notifications.') }}</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openWebhookModal" data-test="add-webhook-button">
            {{ __('Add webhook') }}
        </flux:button>
    </div>

    @if ($generatedWebhookSecret !== null)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/40" data-test="generated-webhook-secret-callout">
            <div class="flex items-start gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                <div class="min-w-0 space-y-2">
                    <flux:text class="font-medium text-amber-900 dark:text-amber-200">
                        {{ __('Copy this signing secret now. It will not be shown again. Use it to verify the X-Platform-Signature header.') }}
                    </flux:text>
                    <code class="block overflow-x-auto rounded-lg bg-amber-100 px-3 py-2 font-mono text-sm text-amber-900 dark:bg-amber-900/40 dark:text-amber-100" data-test="generated-webhook-secret-value">{{ $generatedWebhookSecret }}</code>
                </div>
            </div>
        </div>
    @endif

    <x-admin.card class="!p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-2.5">{{ __('Event type') }}</th>
                        <th class="px-4 py-2.5">{{ __('URL') }}</th>
                        <th class="px-4 py-2.5">{{ __('Status') }}</th>
                        <th class="px-4 py-2.5">{{ __('Last delivery') }}</th>
                        <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->webhooks as $webhook)
                        <tr wire:key="webhook-{{ $webhook->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-mono text-zinc-900 dark:text-white">{{ $webhook->event_type }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $webhook->target_url }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match ($webhook->status->value) {
                                    'active' => 'green',
                                    'paused' => 'red',
                                    default => 'zinc',
                                }">
                                    {{ \Illuminate\Support\Str::headline($webhook->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                @if ($webhook->latestDelivery?->last_attempt_at !== null)
                                    {{ $webhook->latestDelivery->last_attempt_at->diffForHumans() }}
                                    @if ($webhook->latestDelivery->response_code !== null)
                                        <flux:badge size="sm" :color="$webhook->latestDelivery->response_code < 300 ? 'green' : 'red'">
                                            {{ $webhook->latestDelivery->response_code }}
                                        </flux:badge>
                                    @endif
                                @else
                                    {{ __('Never') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    @if ($webhook->status !== \App\Enums\WebhookSubscriptionStatus::Disabled)
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            wire:click="toggleWebhookStatus({{ $webhook->id }})"
                                            data-test="toggle-webhook-{{ $webhook->id }}"
                                        >
                                            {{ $webhook->status === \App\Enums\WebhookSubscriptionStatus::Active ? __('Pause') : __('Resume') }}
                                        </flux:button>
                                    @endif
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                        wire:click="openWebhookModal({{ $webhook->id }})"
                                        aria-label="{{ __('Edit webhook') }}"
                                        data-test="edit-webhook-{{ $webhook->id }}"
                                    />
                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="deleteWebhook({{ $webhook->id }})"
                                        wire:confirm="{{ __('Delete this webhook subscription? Pending deliveries will be discarded.') }}"
                                        aria-label="{{ __('Delete webhook') }}"
                                        data-test="delete-webhook-{{ $webhook->id }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <flux:text>{{ __('No webhook subscriptions yet. Add one to receive real-time event notifications.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if ($this->recentDeliveries->isNotEmpty())
        <div>
            <flux:heading size="lg">{{ __('Recent deliveries') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The latest webhook delivery attempts across all subscriptions.') }}</flux:text>
        </div>

        <x-admin.card class="!p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="px-4 py-2.5">{{ __('Event type') }}</th>
                            <th class="px-4 py-2.5">{{ __('Status') }}</th>
                            <th class="px-4 py-2.5">{{ __('Response') }}</th>
                            <th class="px-4 py-2.5">{{ __('Attempts') }}</th>
                            <th class="px-4 py-2.5">{{ __('Last attempt') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->recentDeliveries as $delivery)
                            <tr wire:key="delivery-{{ $delivery->id }}">
                                <td class="px-4 py-3 font-mono text-zinc-900 dark:text-white">{{ $delivery->subscription->event_type }}</td>
                                <td class="px-4 py-3"><x-admin.status-badge :status="$delivery->status" /></td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $delivery->response_code ?? __('No response') }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $delivery->attempt_count }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $delivery->last_attempt_at?->diffForHumans() ?? __('Pending') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endif

    <flux:modal name="webhook-form" class="md:max-w-md">
        <form wire:submit="saveWebhook" class="space-y-4">
            <flux:heading size="lg">
                {{ $editingWebhookId !== null ? __('Edit webhook') : __('Add webhook') }}
            </flux:heading>

            <flux:field>
                <flux:label>{{ __('Event type') }}</flux:label>
                <flux:select wire:model="webhookEventType" data-test="webhook-event-type-select">
                    @foreach ($this->webhookEventTypes as $eventType)
                        <flux:select.option value="{{ $eventType }}">{{ $eventType }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="webhookEventType" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Endpoint URL') }}</flux:label>
                <flux:input type="url" wire:model="webhookUrl" placeholder="https://example.com/webhooks" data-test="webhook-url-input" />
                <flux:error name="webhookUrl" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="save-webhook-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="generate-token" class="md:max-w-md">
        <form wire:submit="generateToken" class="space-y-4">
            <flux:heading size="lg">{{ __('Generate API token') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Token name') }}</flux:label>
                <flux:input wire:model="newTokenName" :placeholder="__('My integration')" data-test="new-token-name-input" />
                <flux:error name="newTokenName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Abilities') }}</flux:label>
                <div class="grid max-h-64 grid-cols-1 gap-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 sm:grid-cols-2 dark:border-zinc-700">
                    @foreach ($this->availableAbilities as $ability => $description)
                        <flux:checkbox
                            wire:model="newTokenAbilities"
                            value="{{ $ability }}"
                            label="{{ $ability }}"
                            data-test="ability-{{ $ability }}"
                        />
                    @endforeach
                </div>
                <flux:error name="newTokenAbilities" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="confirm-generate-token">
                    {{ __('Generate') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
