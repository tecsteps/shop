<div>
    <flux:heading size="xl" class="mb-6">Developers</flux:heading>

    {{-- API Tokens Section --}}
    <div class="mb-8">
        <flux:heading size="lg" class="mb-2">API Tokens</flux:heading>
        <flux:text class="mb-4 text-zinc-500">Manage personal access tokens for the Admin API.</flux:text>

        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500">API token management coming soon.</flux:text>
        </div>
    </div>

    <flux:separator class="my-8" />

    {{-- Webhooks Section --}}
    <div>
        <flux:heading size="lg" class="mb-2">Webhooks</flux:heading>
        <flux:text class="mb-4 text-zinc-500">Manage webhook subscriptions for real-time event notifications.</flux:text>

        @if($this->webhooks->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon name="code-bracket" class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
                <flux:heading size="md" class="mb-2">No webhook subscriptions</flux:heading>
                <flux:text class="text-zinc-500">Add webhooks to receive real-time notifications about events in your store.</flux:text>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">Event Type</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">URL</th>
                            <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach($this->webhooks as $webhook)
                            <tr>
                                <td class="px-4 py-3 font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $webhook->event_type }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $webhook->target_url }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge :color="$webhook->status->value === 'active' ? 'green' : ($webhook->status->value === 'paused' ? 'red' : 'zinc')">
                                        {{ ucfirst($webhook->status->value) }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
