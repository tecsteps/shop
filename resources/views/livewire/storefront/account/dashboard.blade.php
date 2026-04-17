<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <flux:heading size="xl">My Account</flux:heading>
    <p class="mt-1 text-zinc-600 dark:text-zinc-400">Welcome back, {{ Str::before($customer->name, ' ') }}</p>

    {{-- Quick Links --}}
    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('storefront.account.orders') }}"
           class="rounded-lg border border-zinc-200 p-5 transition hover:border-zinc-400 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-500">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Order history</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">View past orders</p>
        </a>

        <a href="{{ route('storefront.account.addresses') }}"
           class="rounded-lg border border-zinc-200 p-5 transition hover:border-zinc-400 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-500">
            <h3 class="font-semibold text-zinc-900 dark:text-white">Addresses</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage shipping addresses</p>
        </a>

        <form method="POST" action="{{ route('storefront.logout') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-lg border border-zinc-200 p-5 text-left transition hover:border-zinc-400 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-500">
                <h3 class="font-semibold text-zinc-900 dark:text-white">Log out</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Sign out of your account</p>
            </button>
        </form>
    </div>

    {{-- Profile --}}
    <div class="mt-10">
        <flux:heading size="lg">Profile</flux:heading>
        <form wire:submit="updateProfile" class="mt-4 max-w-md space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" :value="$customer->email" disabled />
            </flux:field>

            <flux:checkbox wire:model="marketingOptIn" label="Subscribe to marketing emails" />

            <flux:button type="submit" variant="primary">Save changes</flux:button>
        </form>
    </div>

    {{-- Recent Orders --}}
    <div class="mt-10">
        <flux:heading size="lg">Recent Orders</flux:heading>

        @if($recentOrders->isEmpty())
            <p class="mt-4 text-zinc-500 dark:text-zinc-400">You have not placed any orders yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">Order</th>
                            <th class="pb-3 pr-4 font-medium">Date</th>
                            <th class="pb-3 pr-4 font-medium">Status</th>
                            <th class="pb-3 pr-4 font-medium">Total</th>
                            <th class="pb-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($recentOrders as $order)
                            <tr wire:key="recent-order-{{ $order->id }}">
                                <td class="py-3 pr-4 font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</td>
                                <td class="py-3 pr-4 text-zinc-600 dark:text-zinc-400">{{ \Carbon\Carbon::parse($order->placed_at)->format('M d, Y') }}</td>
                                <td class="py-3 pr-4">
                                    @php
                                        $statusColor = match($order->status) {
                                            \App\Enums\OrderStatus::Pending => 'yellow',
                                            \App\Enums\OrderStatus::Paid => 'green',
                                            \App\Enums\OrderStatus::Fulfilled => 'blue',
                                            \App\Enums\OrderStatus::Cancelled => 'zinc',
                                            \App\Enums\OrderStatus::Refunded => 'red',
                                        };
                                    @endphp
                                    <flux:badge color="{{ $statusColor }}" size="sm">{{ ucfirst($order->status->value) }}</flux:badge>
                                </td>
                                <td class="py-3 pr-4 text-zinc-900 dark:text-white">${{ number_format($order->total_amount / 100, 2) }}</td>
                                <td class="py-3">
                                    <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                                       class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
