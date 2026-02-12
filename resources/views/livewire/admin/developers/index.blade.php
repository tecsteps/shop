<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Developers</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Webhooks</flux:heading>
        <flux:button wire:click="openWebhookModal" variant="primary" icon="plus">Add webhook</flux:button>
    </div>

    <div class="max-w-3xl">
        @if ($webhooks->count() > 0)
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                            <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">URL</th>
                            <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Topic</th>
                            <th class="px-4 py-3 text-center font-medium text-zinc-500 dark:text-zinc-400">Active</th>
                            <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Failures</th>
                            <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($webhooks as $webhook)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="webhook-{{ $webhook->id }}">
                                <td class="px-4 py-3">
                                    <span class="text-zinc-900 dark:text-zinc-100 font-mono text-xs break-all">{{ $webhook->url }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge size="sm" color="zinc">{{ $webhook->topic }}</flux:badge>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <flux:badge size="sm" :color="$webhook->is_active ? 'green' : 'zinc'">
                                        {{ $webhook->is_active ? 'Yes' : 'No' }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                                    {{ $webhook->failure_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button wire:click="toggleWebhook({{ $webhook->id }})" size="sm" variant="ghost" :icon="$webhook->is_active ? 'pause' : 'play'" />
                                        <flux:button wire:click="openWebhookModal({{ $webhook->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                        <flux:button wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="Delete this webhook?" size="sm" variant="ghost" icon="trash" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon name="code-bracket" variant="outline" class="mx-auto size-12 text-zinc-400" />
                <flux:heading size="md" class="mt-4">No webhooks</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Create a webhook subscription to receive event notifications.</flux:text>
                <flux:button wire:click="openWebhookModal" variant="primary" class="mt-4">Add webhook</flux:button>
            </div>
        @endif
    </div>

    {{-- Webhook Modal --}}
    @if ($showWebhookModal)
        <flux:modal name="webhook-form" :show="true" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $editingWebhookId ? 'Edit' : 'Add' }} webhook</flux:heading>

                <flux:field>
                    <flux:label>Endpoint URL</flux:label>
                    <flux:input wire:model="webhookUrl" type="url" placeholder="https://example.com/webhooks" />
                    <flux:error name="webhookUrl" />
                </flux:field>

                <flux:field>
                    <flux:label>Topic</flux:label>
                    <flux:select wire:model="webhookTopic">
                        <flux:select.option value="">Select a topic</flux:select.option>
                        <flux:select.option value="orders/create">orders/create</flux:select.option>
                        <flux:select.option value="orders/updated">orders/updated</flux:select.option>
                        <flux:select.option value="orders/cancelled">orders/cancelled</flux:select.option>
                        <flux:select.option value="products/create">products/create</flux:select.option>
                        <flux:select.option value="products/update">products/update</flux:select.option>
                        <flux:select.option value="products/delete">products/delete</flux:select.option>
                        <flux:select.option value="customers/create">customers/create</flux:select.option>
                        <flux:select.option value="customers/update">customers/update</flux:select.option>
                        <flux:select.option value="inventory/update">inventory/update</flux:select.option>
                        <flux:select.option value="checkouts/create">checkouts/create</flux:select.option>
                        <flux:select.option value="refunds/create">refunds/create</flux:select.option>
                    </flux:select>
                    <flux:error name="webhookTopic" />
                </flux:field>

                <flux:field>
                    <flux:label>Secret</flux:label>
                    <flux:input wire:model="webhookSecret" placeholder="Shared secret for HMAC verification" />
                    <flux:description>Used to sign webhook payloads for verification.</flux:description>
                    <flux:error name="webhookSecret" />
                </flux:field>

                <flux:switch wire:model="webhookActive" label="Active" />

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showWebhookModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button wire:click="saveWebhook" variant="primary">Save webhook</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
