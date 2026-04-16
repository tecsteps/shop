<div class="flex flex-col gap-6">
    <flux:heading size="xl">Dashboard</flux:heading>
    <flux:subheading>Welcome back to {{ $currentStore->name }}.</flux:subheading>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <div class="text-xs font-medium uppercase text-zinc-500">Orders today</div>
            <div class="mt-2 text-3xl font-bold">{{ $kpis['orders_today'] }}</div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <div class="text-xs font-medium uppercase text-zinc-500">Revenue (30d)</div>
            <div class="mt-2 text-3xl font-bold">{{ $currentStore->default_currency }} {{ number_format($kpis['revenue_30d'] / 100, 2) }}</div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <div class="text-xs font-medium uppercase text-zinc-500">Active products</div>
            <div class="mt-2 text-3xl font-bold">{{ $kpis['products_active'] }}</div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <div class="text-xs font-medium uppercase text-zinc-500">Customers</div>
            <div class="mt-2 text-3xl font-bold">{{ $kpis['customers_total'] }}</div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-5 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        <flux:heading size="lg" class="mb-4">Recent orders</flux:heading>
        @if ($orders->isEmpty())
            <div class="py-6 text-center text-zinc-500">No orders yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Order</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($orders as $order)
                        <flux:table.row>
                            <flux:table.cell>{{ $order->order_number }}</flux:table.cell>
                            <flux:table.cell>{{ $order->placed_at?->format('Y-m-d H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">{{ $order->financial_status->value }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="xs" variant="ghost" href="{{ route('admin.orders.show', $order) }}" wire:navigate>View</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
