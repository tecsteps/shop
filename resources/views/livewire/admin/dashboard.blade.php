<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl" level="1">Dashboard</flux:heading>

        <flux:select wire:model.live="dateRange" class="w-48">
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="last_7_days">Last 7 days</flux:select.option>
            <flux:select.option value="last_30_days">Last 30 days</flux:select.option>
            <flux:select.option value="custom">Custom range</flux:select.option>
        </flux:select>
    </div>

    @if($dateRange === 'custom')
        <div class="mb-6 flex gap-4">
            <flux:input type="date" wire:model.live.debounce.500ms="customStartDate" label="Start date" />
            <flux:input type="date" wire:model.live.debounce.500ms="customEndDate" label="End date" />
        </div>
    @endif

    {{-- KPI Tiles --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" wire:loading.class="opacity-50">
        @php $kpis = $this->kpis; @endphp
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Total Sales</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formatCurrency($kpis['totalSales']) }}</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" :color="$kpis['salesChange'] >= 0 ? 'green' : 'red'">
                    {{ $kpis['salesChange'] >= 0 ? '+' : '' }}{{ $kpis['salesChange'] }}%
                </flux:badge>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($kpis['ordersCount']) }}</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" :color="$kpis['ordersChange'] >= 0 ? 'green' : 'red'">
                    {{ $kpis['ordersChange'] >= 0 ? '+' : '' }}{{ $kpis['ordersChange'] }}%
                </flux:badge>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Avg Order</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formatCurrency($kpis['averageOrderValue']) }}</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" :color="$kpis['aovChange'] >= 0 ? 'green' : 'red'">
                    {{ $kpis['aovChange'] >= 0 ? '+' : '' }}{{ $kpis['aovChange'] }}%
                </flux:badge>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Visitors</flux:text>
            <flux:heading size="xl" class="mt-1">0</flux:heading>
            <div class="mt-2">
                <flux:badge size="sm" color="zinc">0%</flux:badge>
            </div>
        </div>
    </div>

    {{-- Top Products + Recent Orders --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Top Products --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Top products</flux:heading>
            @if(count($this->topProducts) > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Product</flux:table.column>
                        <flux:table.column>Sold</flux:table.column>
                        <flux:table.column>Revenue</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->topProducts as $product)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $product['title'] }}</flux:table.cell>
                                <flux:table.cell>{{ $product['units_sold'] }}</flux:table.cell>
                                <flux:table.cell>{{ $this->formatCurrency($product['revenue']) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:text class="text-zinc-500">No sales data for this period.</flux:text>
            @endif
        </div>

        {{-- Recent Orders --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Recent orders</flux:heading>
            @if($this->recentOrders->count() > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column>Customer</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->recentOrders as $order)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                                        {{ $order->order_number }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $order->customer?->name ?? 'Guest' }}</flux:table.cell>
                                <flux:table.cell>{{ $this->formatCurrency($order->total_amount) }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="match($order->financial_status->value) { 'paid' => 'green', 'pending' => 'zinc', 'refunded', 'partially_refunded' => 'yellow', default => 'red' }">
                                        {{ ucfirst(str_replace('_', ' ', $order->financial_status->value)) }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:text class="text-zinc-500">No orders yet.</flux:text>
            @endif
        </div>
    </div>
</div>
