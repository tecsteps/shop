@php
    $statusVariant = [
        'pending' => 'warning',
        'paid' => 'success',
        'fulfilled' => 'accent',
        'cancelled' => 'neutral',
        'refunded' => 'danger',
    ];
@endphp
<div class="mx-auto max-w-5xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Account', 'url' => route('account.dashboard')],
        ['label' => 'Orders'],
    ]" />

    <flux:heading size="xl" class="mb-6">Order history</flux:heading>

    @if ($orders->isEmpty())
        <flux:callout icon="shopping-bag">
            You have no orders yet.
            <a href="{{ route('storefront.collections.index') }}" class="ml-1 underline">Start shopping</a>.
        </flux:callout>
    @else
        <div class="hidden overflow-hidden rounded-lg border border-zinc-200 sm:block dark:border-zinc-800">
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
                    @foreach ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" data-testid="order-row">
                            <td class="px-4 py-3 font-medium">#{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                {{ $order->placed_at?->format('M j, Y') }}
                            </td>
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

        <ul class="space-y-3 sm:hidden">
            @foreach ($orders as $order)
                <li wire:key="order-card-{{ $order->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">#{{ $order->order_number }}</span>
                        <x-storefront.badge :variant="$statusVariant[$order->status->value] ?? 'neutral'">
                            {{ ucfirst($order->status->value) }}
                        </x-storefront.badge>
                    </div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $order->placed_at?->format('M j, Y') }}
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                        <a href="{{ route('account.orders.show', $order->order_number) }}" class="text-sm font-medium underline">View</a>
                    </div>
                </li>
            @endforeach
        </ul>

        <x-storefront.pagination :paginator="$orders" class="mt-8" />
    @endif
</div>
