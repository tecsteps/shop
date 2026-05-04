<x-storefront.account-shell :customer="$customer">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('account.dashboard')],
        ['label' => $isDashboard ? 'Overview' : 'Orders'],
    ]" />

    <div class="mt-8 space-y-6">
        @if ($isDashboard)
            <div>
                <h2 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Welcome back, {{ Str::before($customer->name ?: $customer->email, ' ') }}!</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Review orders and manage saved checkout details.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('account.orders.index') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:bg-zinc-900">
                    <flux:icon name="shopping-bag" class="size-6 text-zinc-500 dark:text-zinc-400" />
                    <h3 class="mt-4 font-semibold text-zinc-950 dark:text-white">Order history</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">View all your orders.</p>
                </a>

                <a href="{{ route('account.addresses.index') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:bg-zinc-900">
                    <flux:icon name="map-pin" class="size-6 text-zinc-500 dark:text-zinc-400" />
                    <h3 class="mt-4 font-semibold text-zinc-950 dark:text-white">Addresses</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage your addresses.</p>
                </a>

                <form method="POST" action="{{ route('account.logout') }}" class="rounded-lg border border-zinc-200 bg-white p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:bg-zinc-900">
                    @csrf
                    <button type="submit" class="block w-full text-left" data-test="customer-dashboard-logout-button">
                        <flux:icon name="arrow-right-start-on-rectangle" class="size-6 text-zinc-500 dark:text-zinc-400" />
                        <span class="mt-4 block font-semibold text-zinc-950 dark:text-white">Log out</span>
                        <span class="mt-1 block text-sm text-zinc-600 dark:text-zinc-400">End this customer session.</span>
                    </button>
                </form>
            </div>
        @else
            <div>
                <h2 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Order History</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">All orders placed from this account.</p>
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <h3 class="font-semibold text-zinc-950 dark:text-white">{{ $isDashboard ? 'Recent Orders' : 'Orders' }}</h3>
            </div>

            @forelse ($orders as $order)
                <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="grid gap-3 border-b border-zinc-200 px-5 py-4 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                    <div>
                        <p class="font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:badge>{{ Str::headline($order->financial_status->value) }}</flux:badge>
                        <flux:badge>{{ Str::headline($order->fulfillment_status->value) }}</flux:badge>
                    </div>
                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="justify-start sm:justify-end" />
                </a>
            @empty
                <div class="p-10 text-center">
                    <flux:icon name="shopping-bag" class="mx-auto size-10 text-zinc-400 dark:text-zinc-600" />
                    <p class="mt-4 font-medium text-zinc-950 dark:text-white">No orders yet</p>
                    <flux:button :href="route('collections.index')" wire:navigate variant="primary" class="mt-6">
                        Browse products
                    </flux:button>
                </div>
            @endforelse
        </div>
    </div>
</x-storefront.account-shell>
