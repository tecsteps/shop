<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

        <flux:dropdown>
            <flux:button icon-trailing="chevron-down">
                {{ match($dateRange) {
                    'today' => __('Today'),
                    'last_7_days' => __('Last 7 days'),
                    'last_30_days' => __('Last 30 days'),
                    'custom' => __('Custom range'),
                    default => __('Last 30 days'),
                } }}
            </flux:button>
            <flux:menu>
                <flux:menu.item wire:click="$set('dateRange', 'today')">{{ __('Today') }}</flux:menu.item>
                <flux:menu.item wire:click="$set('dateRange', 'last_7_days')">{{ __('Last 7 days') }}</flux:menu.item>
                <flux:menu.item wire:click="$set('dateRange', 'last_30_days')">{{ __('Last 30 days') }}</flux:menu.item>
                <flux:menu.item wire:click="$set('dateRange', 'custom')">{{ __('Custom range') }}</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    @if($dateRange === 'custom')
        <div class="flex gap-4 mb-6">
            <flux:input type="date" wire:model.live="customStartDate" label="{{ __('Start date') }}" />
            <flux:input type="date" wire:model.live="customEndDate" label="{{ __('End date') }}" />
        </div>
    @endif

    {{-- KPI Tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8" wire:loading.class="opacity-50">
        {{-- Total Sales --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Total Sales') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedTotalSales() }}</flux:heading>
            <div class="mt-2">
                <flux:badge :color="$salesChange >= 0 ? 'green' : 'red'" size="sm">
                    {{ $salesChange >= 0 ? '+' : '' }}{{ $salesChange }}%
                </flux:badge>
            </div>
        </div>

        {{-- Orders Count --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Orders') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
            <div class="mt-2">
                <flux:badge :color="$ordersChange >= 0 ? 'green' : 'red'" size="sm">
                    {{ $ordersChange >= 0 ? '+' : '' }}{{ $ordersChange }}%
                </flux:badge>
            </div>
        </div>

        {{-- Average Order Value --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Avg Order Value') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->formattedAov() }}</flux:heading>
            <div class="mt-2">
                <flux:badge :color="$aovChange >= 0 ? 'green' : 'red'" size="sm">
                    {{ $aovChange >= 0 ? '+' : '' }}{{ $aovChange }}%
                </flux:badge>
            </div>
        </div>

        {{-- Conversion Rate placeholder --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Conversion Rate') }}</flux:text>
            <flux:heading size="xl" class="mt-1">-</flux:heading>
            <div class="mt-2">
                <flux:badge color="zinc" size="sm">{{ __('N/A') }}</flux:badge>
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Recent orders') }}</flux:heading>
        </div>
        <div class="overflow-x-auto">
            @if(count($recentOrders) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left p-3 font-medium text-zinc-500">{{ __('Order') }}</th>
                            <th class="text-left p-3 font-medium text-zinc-500">{{ __('Customer') }}</th>
                            <th class="text-left p-3 font-medium text-zinc-500">{{ __('Total') }}</th>
                            <th class="text-left p-3 font-medium text-zinc-500">{{ __('Payment') }}</th>
                            <th class="text-left p-3 font-medium text-zinc-500">{{ __('Fulfillment') }}</th>
                            <th class="text-left p-3 font-medium text-zinc-500">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-3">
                                    <a href="{{ route('admin.orders.show', $order['id']) }}" class="text-accent hover:underline" wire:navigate>
                                        {{ $order['order_number'] }}
                                    </a>
                                </td>
                                <td class="p-3">{{ $order['email'] }}</td>
                                <td class="p-3">${{ number_format($order['total_amount'] / 100, 2) }}</td>
                                <td class="p-3">
                                    <flux:badge size="sm" :color="match($order['financial_status']) {
                                        'paid' => 'green',
                                        'pending' => 'yellow',
                                        'refunded' => 'red',
                                        'partially_refunded' => 'orange',
                                        default => 'zinc',
                                    }">{{ str_replace('_', ' ', ucfirst($order['financial_status'])) }}</flux:badge>
                                </td>
                                <td class="p-3">
                                    <flux:badge size="sm" :color="match($order['fulfillment_status']) {
                                        'fulfilled' => 'green',
                                        'partial' => 'yellow',
                                        'unfulfilled' => 'zinc',
                                        default => 'zinc',
                                    }">{{ ucfirst($order['fulfillment_status']) }}</flux:badge>
                                </td>
                                <td class="p-3 text-zinc-500">{{ $order['placed_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-8 text-center">
                    <flux:text class="text-zinc-500">{{ __('No orders yet.') }}</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
