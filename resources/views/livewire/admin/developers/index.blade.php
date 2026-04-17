<div>
    <flux:heading size="xl" class="mb-6">Developers</flux:heading>

    {{-- Webhook Subscriptions Section --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Webhook Subscriptions</flux:heading>
            <flux:button variant="primary" size="sm" wire:click="showCreateForm">
                Create Subscription
            </flux:button>
        </div>

        {{-- Create Webhook Form --}}
        @if ($showCreateWebhook)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="base" class="mb-4">New Webhook Subscription</flux:heading>
                <form wire:submit="createWebhookSubscription" class="space-y-4">
                    <flux:field>
                        <flux:select wire:model="webhookEventType" label="Event Type">
                            <option value="">Select event...</option>
                            <option value="order.created">order.created</option>
                            <option value="order.paid">order.paid</option>
                            <option value="order.fulfilled">order.fulfilled</option>
                            <option value="order.cancelled">order.cancelled</option>
                            <option value="order.refunded">order.refunded</option>
                            <option value="product.created">product.created</option>
                            <option value="product.updated">product.updated</option>
                            <option value="customer.created">customer.created</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:input wire:model="webhookTargetUrl" label="Target URL" placeholder="https://example.com/webhooks" />
                    </flux:field>

                    <flux:field>
                        <flux:input wire:model="webhookSecret" label="Secret (auto-generated if empty)" placeholder="Leave empty to auto-generate" />
                    </flux:field>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" size="sm">Create</flux:button>
                        <flux:button type="button" variant="ghost" size="sm" wire:click="hideCreateForm">Cancel</flux:button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Subscriptions List --}}
        @if ($subscriptions->isEmpty())
            <div class="text-center py-12">
                <flux:icon name="bell-slash" class="size-12 mx-auto text-zinc-400 dark:text-zinc-500 mb-4" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">No webhook subscriptions yet.</flux:text>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Target URL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Failures</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($subscriptions as $subscription)
                            <tr wire:key="sub-{{ $subscription->id }}">
                                <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100 font-mono">
                                    {{ $subscription->event_type }}
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400 max-w-xs truncate">
                                    {{ $subscription->target_url }}
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge :variant="$subscription->status === 'active' ? 'success' : ($subscription->status === 'paused' ? 'warning' : 'danger')" size="sm">
                                        {{ ucfirst($subscription->status) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $subscription->consecutive_failures }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button variant="ghost" size="sm" wire:click="viewDeliveries({{ $subscription->id }})">
                                            History
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" wire:click="toggleSubscriptionStatus({{ $subscription->id }})">
                                            {{ $subscription->status === 'active' ? 'Pause' : 'Activate' }}
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" wire:click="deleteWebhookSubscription({{ $subscription->id }})" wire:confirm="Are you sure you want to delete this subscription?">
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Delivery History Modal --}}
        @if ($showDeliveries)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="base">Delivery History</flux:heading>
                    <flux:button variant="ghost" size="sm" wire:click="closeDeliveries">Close</flux:button>
                </div>

                @if (count($deliveries) === 0)
                    <flux:text class="text-zinc-500 dark:text-zinc-400">No deliveries yet.</flux:text>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">ID</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Event</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">HTTP Code</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Attempts</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($deliveries as $delivery)
                                    <tr wire:key="delivery-{{ $delivery->id }}">
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">#{{ $delivery->id }}</td>
                                        <td class="px-3 py-2 text-sm font-mono text-zinc-900 dark:text-zinc-100">{{ $delivery->event_type }}</td>
                                        <td class="px-3 py-2">
                                            <flux:badge :variant="$delivery->status === 'success' ? 'success' : ($delivery->status === 'pending' ? 'warning' : 'danger')" size="sm">
                                                {{ ucfirst($delivery->status) }}
                                            </flux:badge>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $delivery->response_status ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $delivery->attempt_count }}</td>
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $delivery->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
