<div class="flex flex-col gap-6">
    <flux:heading size="xl">{{ $customer->name ?? $customer->email }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <flux:heading size="lg" class="mb-3">Profile</flux:heading>
            <div class="text-sm"><div class="text-zinc-500">Email</div><div>{{ $customer->email }}</div></div>
            <div class="mt-3 text-sm"><div class="text-zinc-500">Marketing opt-in</div><div>{{ $customer->marketing_opt_in ? 'Yes' : 'No' }}</div></div>
            <div class="mt-3 text-sm"><div class="text-zinc-500">Joined</div><div>{{ $customer->created_at?->format('Y-m-d') }}</div></div>
        </div>

        <div class="lg:col-span-2 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <flux:heading size="lg" class="mb-3">Orders</flux:heading>
            @if ($customer->orders->isEmpty())
                <div class="text-sm text-zinc-500">No orders.</div>
            @else
                <div class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($customer->orders as $order)
                        <div class="flex justify-between py-3 text-sm">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium hover:underline" wire:navigate>{{ $order->order_number }}</a>
                            <span>{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
