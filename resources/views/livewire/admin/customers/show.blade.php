<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <flux:heading size="xl">{{ $customer->name ?? 'Guest customer' }}</flux:heading>
            <flux:text>{{ $customer->email }}</flux:text>
        </div>

        <flux:button :href="route('admin.customers.index')" wire:navigate>Back to customers</flux:button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
                <flux:heading size="lg">Orders</flux:heading>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($customer->orders->sortByDesc('placed_at') as $order)
                    <div wire:key="customer-order-{{ $order->id }}" class="grid gap-4 p-5 text-sm sm:grid-cols-[1fr_auto]">
                        <div>
                            <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium hover:underline">{{ $order->order_number }}</a>
                            <div class="text-zinc-500">{{ $order->placed_at?->format('M j, Y') }} · {{ $order->financial_status->value }}</div>
                        </div>
                        <div class="font-semibold">{{ \Illuminate\Support\Number::currency($order->total_amount / 100, $order->currency) }}</div>
                    </div>
                @empty
                    <div class="p-10 text-sm text-zinc-500">No orders yet.</div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Profile</flux:heading>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-zinc-500">Marketing</dt><dd>{{ $customer->marketing_opt_in ? 'Opted in' : 'Not opted in' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-zinc-500">Created</dt><dd>{{ $customer->created_at?->format('M j, Y') }}</dd></div>
                </dl>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">Addresses</flux:heading>
                <div class="mt-4 space-y-4 text-sm">
                    @forelse ($customer->addresses as $address)
                        <div wire:key="admin-customer-address-{{ $address->id }}" class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <span class="font-medium">{{ $address->label ?? 'Address' }}</span>
                                @if ($address->is_default)
                                    <flux:badge>Default</flux:badge>
                                @endif
                            </div>
                            @include('storefront.components.address', ['address' => $address->address_json])
                        </div>
                    @empty
                        <div class="text-zinc-500">No saved addresses.</div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
