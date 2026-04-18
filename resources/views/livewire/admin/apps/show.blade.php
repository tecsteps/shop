<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $installation->app?->name ?? 'App' }}</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.apps.index') }}" wire:navigate>Back</flux:button>
    </div>
    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900 space-y-1 text-sm">
        <div>Status: {{ $installation->status?->value ?? $installation->status }}</div>
        <div>Scopes: {{ is_array($installation->scopes_json) ? implode(', ', $installation->scopes_json) : '' }}</div>
        <div>Installed at: {{ $installation->installed_at?->format('Y-m-d H:i') }}</div>
    </div>
    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:heading size="sm">Webhook subscriptions</flux:heading>
        @if ($installation->subscriptions->isEmpty())
            <flux:text class="mt-3 text-zinc-500">No subscriptions.</flux:text>
        @else
            <ul class="mt-3 space-y-1 text-sm">
                @foreach ($installation->subscriptions as $sub)
                    <li wire:key="sub-{{ $sub->id }}">{{ $sub->topic ?? $sub->event ?? $sub->id }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
