<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Welcome back, {{ $customer->name }}!</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $customer->email }}</p>

    {{-- Quick links (spec 04 §10.3) --}}
    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('storefront.account.orders.index') }}"
           class="group rounded-xl border border-gray-200 p-5 transition hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-800">
            <svg class="size-6 text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
            </svg>
            <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Order history</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View all your orders</p>
        </a>

        <a href="{{ route('storefront.account.addresses.index') }}"
           class="group rounded-xl border border-gray-200 p-5 transition hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-800">
            <svg class="size-6 text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Addresses</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your addresses</p>
        </a>

        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <button type="submit"
                    class="group w-full rounded-xl border border-gray-200 p-5 text-left transition hover:shadow-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-800">
                <svg class="size-6 text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
                <span class="mt-3 block text-sm font-semibold text-gray-900 dark:text-white">Log out</span>
                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">Sign out of your account</span>
            </button>
        </form>
    </div>

    {{-- Recent orders --}}
    <div class="mt-12">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent orders</h2>
            <a href="{{ route('storefront.account.orders.index') }}"
               class="text-sm font-medium text-blue-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                View all
            </a>
        </div>

        @if ($recentOrders->isEmpty())
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">You haven't placed any orders yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <th scope="col" class="pb-3 pr-4">Order</th>
                            <th scope="col" class="pb-3 pr-4">Date</th>
                            <th scope="col" class="pb-3 pr-4">Status</th>
                            <th scope="col" class="pb-3 pr-4">Total</th>
                            <th scope="col" class="pb-3"><span class="sr-only">View</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($recentOrders as $order)
                            <tr wire:key="dashboard-order-{{ $order->id }}">
                                <td class="py-3 pr-4 text-sm font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</td>
                                <td class="py-3 pr-4 text-sm text-gray-600 dark:text-gray-300">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td class="py-3 pr-4"><x-storefront::order-status-badge :status="$order->status->value" /></td>
                                <td class="py-3 pr-4 text-sm text-gray-600 dark:text-gray-300">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                                       class="text-sm font-medium text-blue-600 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
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

    {{-- Profile settings --}}
    <div class="mt-12 max-w-md">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile</h2>

        @if ($profileSaved)
            <flux:callout variant="success" class="mt-4">
                <flux:callout.text>Your profile has been updated.</flux:callout.text>
            </flux:callout>
        @endif

        <form wire:submit="updateProfile" class="mt-4 space-y-4">
            <flux:field>
                <flux:label for="profile-name">Name</flux:label>
                <flux:input id="profile-name" type="text" wire:model="name" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:checkbox wire:model="marketing_opt_in" label="Subscribe to marketing emails" />

            <flux:button type="submit" variant="primary">Save</flux:button>
        </form>
    </div>
</div>
