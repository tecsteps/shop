<div>
    <flux:heading size="xl">{{ $installation->app?->name ?? 'App' }}</flux:heading>
    <flux:text class="mt-1">Installed {{ $installation->installed_at?->format('M j, Y') }}</flux:text>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Scopes --}}
        <flux:card class="p-6">
            <flux:heading size="md">Scopes granted</flux:heading>
            <flux:separator class="mt-3" />

            <ul class="mt-4 space-y-2">
                @forelse ($this->scopes as $scope)
                    <li class="flex items-center gap-2 text-sm">
                        <flux:icon.check class="size-4 text-green-500" />
                        <span class="font-mono text-xs text-zinc-700 dark:text-zinc-200">{{ $scope }}</span>
                    </li>
                @empty
                    <li class="text-sm text-zinc-400">No scopes granted.</li>
                @endforelse
            </ul>
        </flux:card>

        {{-- Webhooks --}}
        <flux:card class="p-6 lg:col-span-2">
            <flux:heading size="md">Webhook subscriptions</flux:heading>
            <flux:separator class="mt-3" />

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                            <th class="py-2 pe-3 text-start font-medium">Event type</th>
                            <th class="py-2 pe-3 text-start font-medium">URL</th>
                            <th class="py-2 text-start font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->webhooks as $webhook)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                <td class="py-2.5 pe-3 font-mono text-xs text-zinc-700 dark:text-zinc-200">{{ $webhook->event_type }}</td>
                                <td class="py-2.5 pe-3 text-zinc-500 dark:text-zinc-300">{{ $webhook->target_url }}</td>
                                <td class="py-2.5">
                                    <flux:badge :color="$webhook->status === 'active' ? 'green' : 'red'" size="sm">
                                        {{ ucfirst($webhook->status) }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-zinc-400">No webhook subscriptions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        {{-- Usage stats --}}
        <flux:card class="p-6 lg:col-span-3">
            <flux:heading size="md">Usage</flux:heading>
            <flux:separator class="mt-3" />

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <flux:text>Webhook deliveries</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $this->deliveryCount }}</flux:heading>
                </div>
                <div>
                    <flux:text>Webhooks</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ $this->webhooks->count() }}</flux:heading>
                </div>
                <div>
                    <flux:text>Scopes</flux:text>
                    <flux:heading size="lg" class="mt-1">{{ count($this->scopes) }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>
</div>
