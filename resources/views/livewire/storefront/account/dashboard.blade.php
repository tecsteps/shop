@php
    $statusVariant = [
        'pending' => 'warning',
        'paid' => 'success',
        'fulfilled' => 'accent',
        'cancelled' => 'neutral',
        'refunded' => 'danger',
    ];
@endphp
<div class="mx-auto max-w-5xl space-y-6 px-6 py-12">
    <flux:heading size="xl">Account dashboard</flux:heading>
    @auth('customer')
        <flux:text>Welcome back, {{ auth('customer')->user()->first_name ?? auth('customer')->user()->email }}.</flux:text>
    @endauth

    <div class="grid gap-3 sm:grid-cols-3">
        <a href="{{ route('account.orders.index') }}"
           class="rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
            <flux:heading size="sm">Your orders</flux:heading>
            <flux:text size="sm">Track and manage previous purchases</flux:text>
        </a>
        <a href="{{ route('account.addresses.index') }}"
           class="rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
            <flux:heading size="sm">Addresses</flux:heading>
            <flux:text size="sm">Manage shipping and billing addresses</flux:text>
        </a>
        <form method="POST" action="{{ route('account.logout') }}"
              class="rounded-lg border border-zinc-200 p-4 text-left hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
            @csrf
            <button type="submit" class="w-full text-left">
                <flux:heading size="sm">Sign out</flux:heading>
                <flux:text size="sm">End your current session</flux:text>
            </button>
        </form>
    </div>

    @if ($recentOrders->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-3">Recent orders</flux:heading>
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-3 font-medium">Order</th>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($recentOrders as $order)
                            <tr wire:key="recent-{{ $order->id }}">
                                <td class="px-4 py-3 font-medium">#{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <x-storefront.badge :variant="$statusVariant[$order->status->value] ?? 'neutral'">
                                        {{ ucfirst($order->status->value) }}
                                    </x-storefront.badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('account.orders.show', $order->order_number) }}" class="text-sm font-medium underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
