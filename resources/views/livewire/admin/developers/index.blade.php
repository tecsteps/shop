<div class="space-y-4">
    <flux:heading size="xl">Developers</flux:heading>

    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:heading size="sm">Personal access tokens</flux:heading>
        <form wire:submit="createToken" class="mt-3 grid gap-2 sm:grid-cols-3">
            <flux:input wire:model="tokenName" label="Name" placeholder="CLI token" />
            <flux:input wire:model="abilities" label="Abilities (comma separated)" placeholder="read,write" />
            <flux:button type="submit" variant="primary">Create token</flux:button>
        </form>

        @if ($newToken)
            <flux:callout variant="success" class="mt-3">Copy your token now: <code>{{ $newToken }}</code></flux:callout>
        @endif

        <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($tokens as $token)
                <li wire:key="token-{{ $token->id }}" class="flex items-center justify-between py-2 text-sm">
                    <div>
                        <div class="font-medium">{{ $token->name }}</div>
                        <div class="text-xs text-zinc-500">Last used {{ $token->last_used_at?->format('Y-m-d H:i') ?? 'never' }}</div>
                    </div>
                    <flux:button size="xs" variant="danger" wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke token?">Revoke</flux:button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:heading size="sm">Webhook subscriptions</flux:heading>
        @if ($subscriptions->isEmpty())
            <flux:text class="mt-3 text-zinc-500">No subscriptions.</flux:text>
        @else
            <ul class="mt-3 space-y-1 text-sm">
                @foreach ($subscriptions as $sub)
                    <li wire:key="sub-{{ $sub->id }}">{{ $sub->topic ?? $sub->event ?? 'event' }} &rarr; {{ $sub->endpoint_url ?? $sub->url ?? '' }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
