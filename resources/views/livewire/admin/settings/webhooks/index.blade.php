<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">Webhooks</h1>
        <a href="{{ url('/admin/settings/webhooks/create') }}" class="rounded-full bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-700">New subscription</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-neutral-200 text-left text-xs uppercase text-neutral-500 dark:border-neutral-800">
                    <th class="py-2">Event</th>
                    <th class="py-2">Target URL</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">Failures</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr wire:key="sub-{{ $subscription->id }}" class="border-b border-neutral-100 dark:border-neutral-900">
                        <td class="py-2 font-medium">{{ $subscription->event_type }}</td>
                        <td class="py-2 text-neutral-600 dark:text-neutral-400">{{ $subscription->target_url }}</td>
                        <td class="py-2 capitalize">{{ $subscription->status }}</td>
                        <td class="py-2">{{ $subscription->consecutive_failures }}</td>
                        <td class="py-2 text-right space-x-2">
                            <a href="{{ url('/admin/settings/webhooks/'.$subscription->id.'/edit') }}" class="text-neutral-700 hover:underline dark:text-neutral-300">Edit</a>
                            <a href="{{ url('/admin/settings/webhooks/'.$subscription->id.'/deliveries') }}" class="text-neutral-700 hover:underline dark:text-neutral-300">Deliveries</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-neutral-500">No webhook subscriptions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
