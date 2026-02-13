<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'My Account', 'url' => route('customer.dashboard')],
        ['label' => 'Orders'],
    ]" class="mb-8" />

    <div class="lg:grid lg:grid-cols-12 lg:gap-x-8">
        {{-- Sidebar --}}
        <aside class="lg:col-span-3 mb-8 lg:mb-0">
            @include('livewire.storefront.account.partials.sidebar')
        </aside>

        {{-- Main Content --}}
        <div class="lg:col-span-9">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order History</h1>

            @if($orders->isEmpty())
                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">You haven't placed any orders yet.</p>
                </div>
            @else
                <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Payment</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @foreach($orders as $order)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $order->order_number }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $order->placed_at?->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        @include('livewire.storefront.account.partials.order-status-badge', ['status' => $order->status])
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        @include('livewire.storefront.account.partials.financial-status-badge', ['status' => $order->financial_status])
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-900 dark:text-white">
                                        <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <a href="{{ route('customer.orders.show', $order->order_number) }}"
                                           wire:navigate
                                           class="font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
