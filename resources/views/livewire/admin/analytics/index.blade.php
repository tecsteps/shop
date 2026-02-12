<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Analytics</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Analytics</flux:heading>
        <flux:select wire:model.live="period" class="w-44">
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days">Last 30 days</option>
            <option value="last_90_days">Last 90 days</option>
        </flux:select>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Page Views</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($pageViews) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Unique Visits</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($totalVisits) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Orders</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($totalOrders) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500 dark:text-zinc-400">Revenue</flux:text>
            <flux:heading size="xl" class="mt-1">{{ \App\Support\MoneyFormatter::format($totalRevenue) }}</flux:heading>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
        {{-- Revenue Chart --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Revenue over time</flux:heading>

            @if (count($revenueData) > 0)
                <div class="space-y-2">
                    @php
                        $maxRevenue = max(array_column($revenueData, 'value')) ?: 1;
                    @endphp
                    @foreach ($revenueData as $point)
                        <div class="flex items-center gap-3">
                            <span class="w-16 shrink-0 text-xs text-zinc-500">{{ $point['date'] }}</span>
                            <div class="flex-1 h-4 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" style="width: {{ round($point['value'] / $maxRevenue * 100) }}%"></div>
                            </div>
                            <span class="w-20 text-right text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ \App\Support\MoneyFormatter::format($point['value']) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-zinc-500">No revenue data for this period.</flux:text>
            @endif
        </div>

        {{-- Conversion Funnel --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Conversion funnel</flux:heading>

            @php
                $funnelSteps = [
                    ['label' => 'Page Views', 'key' => 'page_views', 'color' => 'bg-blue-500'],
                    ['label' => 'Product Views', 'key' => 'product_views', 'color' => 'bg-indigo-500'],
                    ['label' => 'Add to Cart', 'key' => 'add_to_cart', 'color' => 'bg-purple-500'],
                    ['label' => 'Checkout Started', 'key' => 'checkout_started', 'color' => 'bg-orange-500'],
                    ['label' => 'Checkout Completed', 'key' => 'checkout_completed', 'color' => 'bg-green-500'],
                ];
                $funnelMax = max($funnel['page_views'], 1);
            @endphp

            <div class="space-y-3">
                @foreach ($funnelSteps as $step)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $step['label'] }}</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($funnel[$step['key']]) }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            <div class="{{ $step['color'] }} h-full rounded-full transition-all" style="width: {{ round($funnel[$step['key']] / $funnelMax * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">Top products by views</flux:heading>

        @if ($topProducts->isNotEmpty())
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-2 text-left font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                        <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Views</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topProducts as $product)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 text-zinc-900 dark:text-zinc-100">{{ $product->product_title ?? 'Unknown' }}</td>
                            <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($product->views) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <flux:text class="text-zinc-500">No product view data for this period.</flux:text>
        @endif
    </div>
</div>
