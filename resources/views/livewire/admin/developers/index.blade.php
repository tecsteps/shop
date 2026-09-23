<div class="grid gap-6">
    @include('livewire.admin.components.page-heading', ['title' => 'Developers', 'description' => 'Manage API access and event notifications for connected services.'])

    @if ($generatedToken)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="Copy this API token now">
            <p>The plain-text token is shown once. Store it somewhere safe before leaving this page.</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <code class="min-w-0 flex-1 break-all rounded-lg border border-amber-300 bg-white/70 px-3 py-2 font-mono text-xs text-zinc-900 dark:border-amber-800 dark:bg-zinc-950 dark:text-zinc-100">{{ $generatedToken }}</code>
                <flux:button variant="filled" wire:click="dismissGeneratedToken">I've copied it</flux:button>
            </div>
        </flux:callout>
    @endif

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="api-tokens-heading">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
            <div>
                <h2 id="api-tokens-heading" class="text-lg font-semibold">API tokens</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage personal access tokens for the Admin API. Tokens are limited to this store.</p>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openTokenModal">Generate new token</flux:button>
        </div>

        @if ($tokens->isEmpty())
            <div class="px-5 py-12 text-center">
                <flux:icon name="key" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="mt-3 text-sm font-medium">No API tokens yet</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Generate a token to connect an integration to this store.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr><th scope="col" class="px-5 py-3 font-medium">Name</th><th scope="col" class="px-5 py-3 font-medium">Last used</th><th scope="col" class="px-5 py-3 font-medium">Created</th><th scope="col" class="px-5 py-3 font-medium">Expires</th><th scope="col" class="px-5 py-3 text-right font-medium">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($tokens as $token)
                            <tr wire:key="api-token-{{ $token->id }}">
                                <td class="px-5 py-4"><span class="block font-medium">{{ $token->name }}</span><span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $token->tokenable?->name }}</span></td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $token->created_at?->format('M j, Y') }}</td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $token->expires_at?->format('M j, Y') ?? 'Never' }}</td>
                                <td class="px-5 py-4 text-right"><flux:button size="sm" variant="danger" wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke this API token? Existing integrations using it will stop working.">Revoke</flux:button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <flux:separator />

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="webhooks-heading">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
            <div>
                <h2 id="webhooks-heading" class="text-lg font-semibold">Webhooks</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage webhook subscriptions for real-time event notifications.</p>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openWebhookModal">Add webhook</flux:button>
        </div>

        @if ($webhooks->isEmpty())
            <div class="px-5 py-12 text-center">
                <flux:icon name="bolt" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="mt-3 text-sm font-medium">No webhook subscriptions yet</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Add an endpoint to receive store events.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr><th scope="col" class="px-5 py-3 font-medium">Event type</th><th scope="col" class="px-5 py-3 font-medium">URL</th><th scope="col" class="px-5 py-3 font-medium">Status</th><th scope="col" class="px-5 py-3 text-right font-medium">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($webhooks as $webhook)
                            @php
                                $webhookFailing = $webhook->latestDelivery?->status === 'failed';
                                $webhookStatusLabel = $webhookFailing ? 'Failing' : \Illuminate\Support\Str::headline($webhook->status);
                                $webhookStatusColor = $webhookFailing ? 'red' : ($webhook->status === 'active' ? 'green' : 'zinc');
                            @endphp
                            <tr wire:key="webhook-{{ $webhook->id }}">
                                <td class="px-5 py-4 font-medium">{{ $webhook->event_type }}</td>
                                <td class="max-w-0 px-5 py-4"><a class="block truncate text-blue-700 hover:underline dark:text-blue-300" href="{{ $webhook->target_url }}" target="_blank" rel="noopener noreferrer">{{ $webhook->target_url }}</a></td>
                                <td class="px-5 py-4"><flux:badge :color="$webhookStatusColor">{{ $webhookStatusLabel }}</flux:badge></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <flux:button size="sm" variant="ghost" wire:click="openWebhookModal({{ $webhook->id }})">Edit</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="Delete this webhook subscription?">Delete</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <p class="border-t border-zinc-200 px-5 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Each webhook receives a unique signing secret. Secrets are encrypted and used to sign event deliveries.</p>
    </section>

    <flux:modal name="generate-token" wire:model="showGenerateTokenModal" class="max-w-xl">
        <form wire:submit="generateToken" class="grid gap-5">
            <div><flux:heading size="lg">Generate API token</flux:heading><flux:subheading class="mt-1">Create a token that can access this store only.</flux:subheading></div>

            <flux:field>
                <flux:label for="developer-token-name">Token name</flux:label>
                <flux:input id="developer-token-name" wire:model="newTokenName" placeholder="My integration" autocomplete="off" />
                <flux:error name="newTokenName" />
            </flux:field>

            <fieldset class="grid gap-3">
                <legend class="text-sm font-medium">API permissions</legend>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Choose the smallest set of permissions the integration needs.</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($tokenAbilityOptions as $ability)
                        <label wire:key="token-ability-{{ $ability }}" class="flex min-h-10 items-center gap-2 rounded-lg border border-zinc-200 px-3 text-sm dark:border-zinc-700">
                            <input type="checkbox" wire:model="tokenAbilities" value="{{ $ability }}" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-600 dark:border-zinc-600 dark:bg-zinc-950">
                            <span>{{ \Illuminate\Support\Str::headline($ability) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('tokenAbilities')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                @error('tokenAbilities.*')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            </fieldset>

            <flux:field>
                <flux:label for="developer-token-expires">Expiry date <span class="font-normal text-zinc-500">(defaults to one year)</span></flux:label>
                <flux:input id="developer-token-expires" type="date" wire:model="tokenExpiresAt" min="{{ now()->addDay()->toDateString() }}" />
                <flux:error name="tokenExpiresAt" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="generateToken">Generate</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="webhook-form" wire:model="showWebhookModal" class="max-w-xl">
        <form wire:submit="saveWebhook" class="grid gap-5">
            <div><flux:heading size="lg">{{ $editingWebhookId === null ? 'Add webhook' : 'Edit webhook' }}</flux:heading><flux:subheading class="mt-1">Choose an event and the endpoint that should receive it.</flux:subheading></div>

            <flux:field>
                <flux:label for="webhook-event-type">Event type</flux:label>
                <select id="webhook-event-type" wire:model="webhookEventType" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">Select an event</option>
                    @foreach ($webhookEventTypes as $eventType)
                        <option value="{{ $eventType }}">{{ $eventType }}</option>
                    @endforeach
                </select>
                <flux:error name="webhookEventType" />
            </flux:field>

            <flux:field>
                <flux:label for="webhook-url">Endpoint URL</flux:label>
                <flux:input id="webhook-url" type="url" wire:model="webhookUrl" placeholder="https://example.com/webhooks" autocomplete="url" />
                <flux:error name="webhookUrl" />
            </flux:field>

            <flux:field>
                <flux:label for="webhook-status">Status</flux:label>
                <select id="webhook-status" wire:model="webhookStatus" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="active">Active</option>
                    <option value="paused">Paused</option>
                    <option value="disabled">Disabled</option>
                </select>
                <flux:error name="webhookStatus" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveWebhook">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
