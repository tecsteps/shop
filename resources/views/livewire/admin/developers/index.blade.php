<div>
    <x-admin.breadcrumbs :items="[['label' => __('Developers')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Developers') }}</flux:heading>

    {{-- API tokens. --}}
    <section class="mb-8">
        <flux:heading size="lg">{{ __('API tokens') }}</flux:heading>
        <flux:text class="mb-4 mt-1 text-sm">{{ __('Manage personal access tokens for the Admin API.') }}</flux:text>

        @if ($generatedToken)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4">
                <flux:callout.heading>{{ __('Copy this token now. It will not be shown again.') }}</flux:callout.heading>
                <flux:callout.text>
                    <code class="mt-2 block break-all rounded bg-zinc-100 p-2 text-xs dark:bg-zinc-800" data-test="generated-token">{{ $generatedToken }}</code>
                </flux:callout.text>
            </flux:callout>
        @endif

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Last used') }}</flux:table.column>
                <flux:table.column>{{ __('Created') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->tokens as $token)
                    <flux:table.row :key="'token-'.$token->id">
                        <flux:table.cell variant="strong">{{ $token->name }}</flux:table.cell>
                        <flux:table.cell>{{ $token->last_used_at?->diffForHumans() ?? __('Never') }}</flux:table.cell>
                        <flux:table.cell>{{ $token->created_at?->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button size="sm" variant="ghost" wire:click="revokeToken({{ $token->id }})" wire:confirm="{{ __('Revoke this token?') }}">{{ __('Revoke') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center">{{ __('No tokens yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            <flux:button variant="primary" icon="plus" wire:click="$set('showTokenModal', true)" data-test="generate-token">{{ __('Generate new token') }}</flux:button>
        </div>
    </section>

    <flux:separator class="my-8" />

    {{-- Webhooks. --}}
    <section>
        <flux:heading size="lg">{{ __('Webhooks') }}</flux:heading>
        <flux:text class="mb-4 mt-1 text-sm">{{ __('Manage webhook subscriptions for real-time event notifications.') }}</flux:text>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Event type') }}</flux:table.column>
                <flux:table.column>{{ __('URL') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->webhooks as $webhook)
                    <flux:table.row :key="'wh-'.$webhook->id">
                        <flux:table.cell variant="strong">{{ $webhook->event_type }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">{{ $webhook->target_url }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$webhook->status->value === 'active' ? 'green' : 'red'">{{ ucfirst($webhook->status->value) }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openWebhookModal({{ $webhook->id }})" :aria-label="__('Edit')" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="{{ __('Delete this webhook?') }}" :aria-label="__('Delete')" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center">{{ __('No webhooks yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            <flux:button variant="primary" icon="plus" wire:click="openWebhookModal" data-test="add-webhook">{{ __('Add webhook') }}</flux:button>
        </div>
    </section>

    {{-- Generate token modal. --}}
    <flux:modal wire:model.self="showTokenModal" name="generate-token" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Generate API token') }}</flux:heading>
            <flux:field>
                <flux:label>{{ __('Token name') }}</flux:label>
                <flux:input wire:model="newTokenName" placeholder="My integration" data-test="token-name" />
                <flux:error name="newTokenName" />
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showTokenModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="generateToken" data-test="submit-token">{{ __('Generate') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Webhook modal. --}}
    <flux:modal wire:model.self="showWebhookModal" name="webhook-form" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingWebhookId ? __('Edit webhook') : __('Add webhook') }}</flux:heading>
            <flux:field>
                <flux:label>{{ __('Event type') }}</flux:label>
                <flux:select wire:model="webhookEventType">
                    @foreach (\App\Livewire\Admin\Developers\Index::EVENT_TYPES as $event)
                        <flux:select.option :value="$event">{{ $event }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Endpoint URL') }}</flux:label>
                <flux:input type="url" wire:model="webhookUrl" placeholder="https://example.com/webhooks" data-test="webhook-url" />
                <flux:error name="webhookUrl" />
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showWebhookModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="saveWebhook" data-test="submit-webhook">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
