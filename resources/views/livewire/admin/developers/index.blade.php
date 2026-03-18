<div>
    <flux:heading size="xl" class="mb-6">{{ __('Developers') }}</flux:heading>

    {{-- API Tokens --}}
    <flux:heading size="lg">{{ __('API tokens') }}</flux:heading>
    <flux:text class="mt-1 mb-4 text-zinc-500">{{ __('Manage personal access tokens for the Admin API.') }}</flux:text>

    @if($generatedToken)
        <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-700 p-4">
            <flux:text class="font-medium text-yellow-800 dark:text-yellow-200">{{ __('Copy this token now. It will not be shown again.') }}</flux:text>
            <code class="mt-2 block text-sm break-all text-yellow-900 dark:text-yellow-100 bg-yellow-100 dark:bg-yellow-900/40 p-2 rounded">{{ $generatedToken }}</code>
        </div>
    @endif

    @if($this->tokens->isNotEmpty())
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="py-2 pr-4 font-medium">{{ __('Name') }}</th>
                        <th class="py-2 pr-4 font-medium">{{ __('Last used') }}</th>
                        <th class="py-2 pr-4 font-medium">{{ __('Created') }}</th>
                        <th class="py-2 font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->tokens as $token)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pr-4">{{ $token->name }}</td>
                            <td class="py-2 pr-4 text-zinc-500">{{ $token->last_used_at?->diffForHumans() ?? __('Never') }}</td>
                            <td class="py-2 pr-4 text-zinc-500">{{ $token->created_at->format('M j, Y') }}</td>
                            <td class="py-2">
                                <flux:button size="sm" variant="danger" wire:click="revokeToken({{ $token->id }})" wire:confirm="{{ __('Are you sure you want to revoke this token?') }}">
                                    {{ __('Revoke') }}
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="flex gap-2 items-end">
        <flux:input wire:model="newTokenName" label="{{ __('Token name') }}" placeholder="{{ __('My integration') }}" class="max-w-xs" />
        <flux:button wire:click="generateToken">{{ __('Generate new token') }}</flux:button>
    </div>

    <flux:separator class="my-8" />

    {{-- Webhooks --}}
    <flux:heading size="lg">{{ __('Webhooks') }}</flux:heading>
    <flux:text class="mt-1 mb-4 text-zinc-500">{{ __('Manage webhook subscriptions for real-time event notifications.') }}</flux:text>

    @if($this->webhooks->isNotEmpty())
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="py-2 pr-4 font-medium">{{ __('Event type') }}</th>
                        <th class="py-2 pr-4 font-medium">{{ __('URL') }}</th>
                        <th class="py-2 pr-4 font-medium">{{ __('Status') }}</th>
                        <th class="py-2 font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->webhooks as $webhook)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pr-4">{{ $webhook->event_type }}</td>
                            <td class="py-2 pr-4 text-zinc-500 truncate max-w-xs">{{ $webhook->target_url }}</td>
                            <td class="py-2 pr-4">
                                <flux:badge :color="match($webhook->status) { 'active' => 'green', 'paused' => 'yellow', default => 'zinc' }">
                                    {{ ucfirst($webhook->status) }}
                                </flux:badge>
                            </td>
                            <td class="py-2 flex gap-1">
                                <flux:button size="sm" wire:click="openWebhookModal({{ $webhook->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="{{ __('Delete this webhook?') }}">{{ __('Delete') }}</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <flux:button wire:click="openWebhookModal">{{ __('+ Add webhook') }}</flux:button>

    {{-- Webhook Modal --}}
    <flux:modal wire:model="showWebhookModal">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingWebhookId ? __('Edit webhook') : __('Add webhook') }}</flux:heading>

            <flux:select wire:model="webhookEventType" label="{{ __('Event type') }}">
                <flux:select.option value="order.created">order.created</flux:select.option>
                <flux:select.option value="order.paid">order.paid</flux:select.option>
                <flux:select.option value="order.fulfilled">order.fulfilled</flux:select.option>
                <flux:select.option value="order.cancelled">order.cancelled</flux:select.option>
                <flux:select.option value="order.refunded">order.refunded</flux:select.option>
            </flux:select>

            <flux:input wire:model="webhookUrl" label="{{ __('Target URL') }}" placeholder="https://example.com/webhook" />

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showWebhookModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="saveWebhook">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
