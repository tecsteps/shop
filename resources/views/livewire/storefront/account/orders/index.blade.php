<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Order History</h1>

    <div class="mt-6 flex flex-col gap-8 lg:flex-row">
        @include('livewire.storefront.account.partials.account-nav')

        <div class="flex-1">
            @if($this->orders->isEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">You have no orders yet.</p>
                    <a href="{{ route('home') }}"
                       class="mt-4 inline-block text-sm font-medium text-zinc-900 hover:underline dark:text-white">
                        Start shopping
                    </a>
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Order</th>
                                <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                                <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Fulfillment</th>
                                <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($this->orders as $order)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('storefront.account.orders.show', ltrim($order->order_number, '#')) }}"
                                           class="font-medium text-zinc-900 hover:underline dark:text-white">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                        {{ $order->placed_at?->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ match($order->status) {
                                                \App\Enums\OrderStatus::Paid => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                \App\Enums\OrderStatus::Fulfilled => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                \App\Enums\OrderStatus::Cancelled => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                \App\Enums\OrderStatus::Refunded => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                            } }}">
                                            {{ ucfirst($order->status->value) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ match($order->fulfillment_status) {
                                                \App\Enums\FulfillmentStatus::Fulfilled => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                \App\Enums\FulfillmentStatus::Partial => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                            } }}">
                                            {{ ucfirst($order->fulfillment_status->value) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->orders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
