<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Account</h1>

    <div class="mt-6 flex flex-col gap-8 lg:flex-row">
        @include('livewire.storefront.account.partials.account-nav')

        <div class="flex-1 space-y-8">
            {{-- Welcome --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Welcome, {{ $this->customer->name ?? 'Customer' }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->customer->email }}</p>
            </div>

            {{-- Recent Orders --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Orders</h2>
                    <a href="{{ route('storefront.account.orders') }}"
                       class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                        View all
                    </a>
                </div>

                @if($this->recentOrders->isEmpty())
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">You have no orders yet.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Order</th>
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                                    <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                                    <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($this->recentOrders as $order)
                                    <tr>
                                        <td class="py-3">
                                            <a href="{{ route('storefront.account.orders.show', ltrim($order->order_number, '#')) }}"
                                               class="font-medium text-zinc-900 hover:underline dark:text-white">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="py-3 text-zinc-500 dark:text-zinc-400">
                                            {{ $order->placed_at?->format('M d, Y') }}
                                        </td>
                                        <td class="py-3">
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
                                        <td class="py-3 text-right">
                                            <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Quick Links --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <a href="{{ route('storefront.account.orders') }}"
                   class="rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                    <h3 class="font-medium text-zinc-900 dark:text-white">Order History</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">View and track your orders</p>
                </a>
                <a href="{{ route('storefront.account.addresses') }}"
                   class="rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                    <h3 class="font-medium text-zinc-900 dark:text-white">Address Book</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage your shipping addresses</p>
                </a>
            </div>
        </div>
    </div>
</div>
