<div class="space-y-6">
    <div><p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Developers</p><h1 class="mt-2 text-3xl font-bold">API & webhooks</h1><p class="mt-2 text-zinc-600 dark:text-zinc-400">Create scoped API tokens and connect external systems to store events.</p></div>

    @if ($plainTextToken)
        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-950" role="alert"><p class="font-semibold">Copy this token now. It will not be shown again.</p><code class="mt-3 block overflow-x-auto rounded-lg bg-white p-3 font-mono">{{ $plainTextToken }}</code></div>
    @endif

    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800" aria-labelledby="token-heading">
        <h2 id="token-heading" class="text-lg font-semibold">Create API token</h2>
        <form wire:submit="createToken" class="mt-5 grid gap-4 md:grid-cols-3">
            <flux:input wire:model="tokenName" label="Token name" required placeholder="Reporting integration" />
            <flux:input wire:model="tokenExpiresAt" label="Expires on (optional)" type="date" />
            <flux:input wire:model="tokenAbilities" label="Abilities (comma-separated)" required />
            <div class="md:col-span-3"><flux:button type="submit" variant="primary">Create token</flux:button></div>
        </form>
        <div class="mt-5 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b border-zinc-200 dark:border-zinc-800"><tr><th class="px-3 py-3">Name</th><th class="px-3 py-3">Abilities</th><th class="px-3 py-3">Last used</th><th class="px-3 py-3">Expires</th><th></th></tr></thead><tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">@forelse ($tokens as $token)<tr><td class="px-3 py-3 font-medium">{{ $token->name }}</td><td class="px-3 py-3">{{ implode(', ', $token->abilities ?? []) }}</td><td class="px-3 py-3">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td class="px-3 py-3">{{ $token->expires_at?->toFormattedDateString() ?? 'Default expiry' }}</td><td class="px-3 py-3 text-right"><flux:button size="sm" variant="danger" wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke this token?">Revoke</flux:button></td></tr>@empty<tr><td colspan="5" class="px-3 py-8 text-center text-zinc-500">No API tokens yet.</td></tr>@endforelse</tbody></table></div>
    </section>

    <section class="space-y-5" aria-labelledby="webhooks-heading">
        <h2 id="webhooks-heading" class="text-lg font-semibold">Webhooks</h2>
        <form wire:submit="createWebhook" class="grid gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:grid-cols-[220px_1fr_auto] dark:bg-zinc-900 dark:ring-zinc-800"><flux:select wire:model="event" label="Event"><option value="order.created">order.created</option><option value="order.paid">order.paid</option><option value="order.fulfilled">order.fulfilled</option><option value="order.refunded">order.refunded</option></flux:select><flux:input wire:model="targetUrl" label="Target URL" type="url" required /><div class="flex items-end"><flux:button type="submit" variant="primary">Add webhook</flux:button></div></form>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800"><table class="min-w-full text-left text-sm"><thead class="bg-zinc-50 dark:bg-zinc-800"><tr><th class="px-5 py-3">Event</th><th class="px-5 py-3">Endpoint</th><th class="px-5 py-3">Status</th><th></th></tr></thead><tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">@forelse($subscriptions as $subscription)<tr><td class="px-5 py-4 font-medium">{{ $subscription->event }}</td><td class="px-5 py-4">{{ $subscription->target_url }}</td><td class="px-5 py-4">{{ ucfirst($subscription->status) }}</td><td class="px-5 py-4 text-right"><flux:button size="sm" variant="ghost" wire:click="pause({{ $subscription->id }})">{{ $subscription->status === 'active' ? 'Pause' : 'Resume' }}</flux:button></td></tr>@empty<tr><td colspan="4" class="px-5 py-10 text-center text-zinc-500">No webhook subscriptions.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>
