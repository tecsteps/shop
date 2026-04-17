<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">Deliveries</h1>
        <a href="{{ url('/admin/settings/webhooks') }}" class="text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-white">Back</a>
    </div>
    <div class="text-sm text-neutral-600 dark:text-neutral-400">
        {{ $subscription->event_type }} &rarr; {{ $subscription->target_url }}
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-neutral-200 text-left text-xs uppercase text-neutral-500 dark:border-neutral-800">
                    <th class="py-2">Event ID</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">Attempts</th>
                    <th class="py-2">HTTP</th>
                    <th class="py-2">Last attempt</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deliveries as $delivery)
                    <tr wire:key="delivery-{{ $delivery->id }}" class="border-b border-neutral-100 dark:border-neutral-900">
                        <td class="py-2 font-mono text-xs">{{ $delivery->event_id }}</td>
                        <td class="py-2 capitalize">{{ $delivery->status }}</td>
                        <td class="py-2">{{ $delivery->attempt_count }}</td>
                        <td class="py-2">{{ $delivery->response_code ?? '-' }}</td>
                        <td class="py-2 text-neutral-500">{{ optional($delivery->last_attempt_at)->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-neutral-500">No deliveries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
