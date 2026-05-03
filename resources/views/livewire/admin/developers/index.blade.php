<div class="space-y-6">
    <div>
        <flux:heading size="xl">Developers</flux:heading>
        <flux:text>API credentials and webhook delivery settings.</flux:text>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg">API tokens</flux:heading>
            @if($generatedToken)
                <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <div class="font-semibold">Copy this token now. It will not be shown again.</div>
                    <code class="mt-2 block break-all rounded bg-white p-3 text-xs text-zinc-950 dark:bg-zinc-900 dark:text-white">{{ $generatedToken }}</code>
                </div>
            @endif

            <form wire:submit="generateToken" class="mt-5 space-y-4">
                <flux:input wire:model="newTokenName" label="Token name" />
                <div class="grid gap-2">
                    <div class="text-sm font-medium">Abilities</div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($availableAbilities as $ability)
                            <label wire:key="ability-{{ $ability }}" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="tokenAbilities" value="{{ $ability }}" class="rounded border-zinc-300">
                                {{ $ability }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <flux:button type="submit" variant="primary">Generate token</flux:button>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-zinc-500">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Name</th>
                            <th class="py-2 pr-4 font-medium">Last used</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($tokens as $token)
                            <tr wire:key="api-token-{{ $token->id }}">
                                <td class="py-3 pr-4 font-medium">{{ $token->name }}</td>
                                <td class="py-3 pr-4 text-zinc-500">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="py-3 pr-4">
                                    <flux:badge :color="$token->revoked_at ? 'zinc' : 'green'">{{ $token->revoked_at ? 'Revoked' : 'Active' }}</flux:badge>
                                </td>
                                <td class="py-3 text-right">
                                    @if(! $token->revoked_at)
                                        <flux:button size="xs" type="button" wire:click="revokeToken({{ $token->id }})">Revoke</flux:button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-sm text-zinc-500">No API tokens.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg">Webhooks</flux:heading>
            <form wire:submit="saveWebhook" class="mt-5 grid gap-4">
                <flux:select wire:model="webhookEventType" label="Event type">
                    @foreach($webhookEventTypes as $eventType)
                        <option value="{{ $eventType }}">{{ $eventType }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="webhookUrl" label="Endpoint URL" type="url" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ $editingWebhookId ? 'Save webhook' : 'Add webhook' }}</flux:button>
                    @if($editingWebhookId)
                        <flux:button type="button" wire:click="$set('editingWebhookId', null)">Cancel</flux:button>
                    @endif
                </div>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-zinc-500">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Event</th>
                            <th class="py-2 pr-4 font-medium">URL</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($webhooks as $webhook)
                            <tr wire:key="webhook-{{ $webhook->id }}">
                                <td class="py-3 pr-4 font-medium">{{ $webhook->event_type }}</td>
                                <td class="max-w-56 truncate py-3 pr-4 text-zinc-500">{{ $webhook->target_url }}</td>
                                <td class="py-3 pr-4"><flux:badge :color="$webhook->status === 'active' ? 'green' : 'zinc'">{{ $webhook->status }}</flux:badge></td>
                                <td class="py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="xs" type="button" wire:click="editWebhook({{ $webhook->id }})">Edit</flux:button>
                                        <flux:button size="xs" type="button" wire:click="deleteWebhook({{ $webhook->id }})">Delete</flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-sm text-zinc-500">No webhook subscriptions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
