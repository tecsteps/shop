<div>
    @include('livewire.admin.components.page-heading', ['title' => 'Analytics', 'description' => 'Understand sales performance and how customers find your store.'])

    <section class="flex flex-wrap items-end gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-label="Analytics filters">
        <div>
            <label for="analytics-range" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">Date range</label>
            <select id="analytics-range" wire:model.live="dateRange" class="min-h-11 rounded-xl border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="today">Today</option>
                <option value="last_7_days">Last 7 days</option>
                <option value="last_30_days">Last 30 days</option>
                <option value="custom">Custom</option>
            </select>
        </div>
        @if ($dateRange === 'custom')
            <div>
                <label for="analytics-start" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">From</label>
                <input id="analytics-start" type="date" wire:model.live="customStartDate" class="min-h-11 rounded-xl border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                @error('customStartDate')<p class="mt-1 text-xs text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="analytics-end" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">To</label>
                <input id="analytics-end" type="date" wire:model.live="customEndDate" class="min-h-11 rounded-xl border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                @error('customEndDate')<p class="mt-1 text-xs text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
            </div>
        @endif
        <div>
            <label for="analytics-channel" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">Sales channel</label>
            <select id="analytics-channel" wire:model.live="channelFilter" class="min-h-11 rounded-xl border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all">All channels</option>
                <option value="storefront">Storefront</option>
                <option value="api">API</option>
            </select>
        </div>
        <div>
            <label for="analytics-device" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">Device type</label>
            <select id="analytics-device" wire:model.live="deviceFilter" class="min-h-11 rounded-xl border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all">All devices</option>
                <option value="desktop">Desktop</option>
                <option value="mobile">Mobile</option>
                <option value="tablet">Tablet</option>
            </select>
        </div>
        <div class="ml-auto flex min-h-11 flex-wrap items-center gap-3">
            @if ($isExporting)
                <span wire:poll.5s="pollExport" class="text-sm text-zinc-500" role="status">Preparing your CSV export…</span>
            @endif
            <button type="button" wire:click="exportCsv" @disabled($isExporting) class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-zinc-300 px-4 text-sm font-medium hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                Export CSV
            </button>
            @if ($exportUrl)
                <a href="{{ $exportUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Download CSV
                </a>
            @endif
        </div>
    </section>
    @if ($exportError)
        <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200" role="alert">{{ $exportError }}</p>
    @endif

    <section class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Analytics metrics">
        @foreach ([['Total sales', number_format($analytics['total_sales'] / 100, 2, '.', ',').' '.$analytics['currency'], 'banknotes'], ['Orders', number_format($analytics['orders_count']), 'shopping-bag'], ['Average order value', number_format($analytics['average_order_value'] / 100, 2, '.', ',').' '.$analytics['currency'], 'receipt-percent'], ['Conversion rate', number_format($analytics['conversion_rate'], 2, '.', ',').'%', 'chart-bar']] as [$label, $value, $icon])
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</p><p class="mt-3 text-2xl font-semibold">{{ $value }}</p></div>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300"><flux:icon name="{{ $icon }}" class="size-5" /></span>
                </div>
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $analytics['from'] }} to {{ $analytics['to'] }} · {{ number_format($analytics['visits_count']) }} visits</p>
            </article>
        @endforeach
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="sales-chart-heading">
            <h2 id="sales-chart-heading" class="font-semibold">Sales over time</h2>
            @if ($analytics['orders_count'] > 0)
                <div class="mt-5 grid gap-3" role="img" aria-label="Daily sales for the selected period">
                    @foreach ($analytics['daily'] as $day)
                        <div class="grid grid-cols-[5.5rem_minmax(0,1fr)_auto] items-center gap-3 text-xs">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ $day['date'] }}</span>
                            <span class="h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><span class="block h-full rounded-full bg-blue-600 dark:bg-blue-400" style="width: {{ $day['revenue_percent'] }}%"></span></span>
                            <span class="font-medium tabular-nums">{{ number_format($day['revenue_amount'] / 100, 2, '.', ',') }} {{ $analytics['currency'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-5 flex min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 text-center dark:border-zinc-800">
                    <flux:icon name="chart-bar" class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-3 text-sm font-medium">No sales in this period</p>
                    <p class="mt-1 max-w-xs text-xs text-zinc-500">Sales will appear here after paid orders are placed.</p>
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="top-products-heading">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-800"><h2 id="top-products-heading" class="font-semibold">Top products</h2></div>
            @if ($analytics['top_products'] !== [])
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400"><tr><th scope="col" class="px-5 py-3">Rank</th><th scope="col" class="px-5 py-3">Product</th><th scope="col" class="px-5 py-3">Units sold</th><th scope="col" class="px-5 py-3">Revenue</th><th scope="col" class="px-5 py-3">Share</th></tr></thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($analytics['top_products'] as $rank => $product)
                                <tr><td class="px-5 py-3 tabular-nums text-zinc-500">{{ $rank + 1 }}</td><th scope="row" class="px-5 py-3 font-medium">{{ $product['title'] }}</th><td class="px-5 py-3 tabular-nums">{{ number_format($product['units_sold']) }}</td><td class="px-5 py-3 tabular-nums">{{ number_format($product['revenue_amount'] / 100, 2, '.', ',') }} {{ $analytics['currency'] }}</td><td class="px-5 py-3 tabular-nums">{{ number_format($product['revenue_percent'], 1) }}%</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-5 py-12 text-center text-sm text-zinc-500">No product sales for this period.</div>
            @endif
        </section>
    </div>

    <section class="mt-5 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="top-referrers-heading">
        <div class="border-b border-zinc-200 p-5 dark:border-zinc-800"><h2 id="top-referrers-heading" class="font-semibold">Top referrers</h2></div>
        @if ($analytics['top_referrers'] !== [])
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400"><tr><th scope="col" class="px-5 py-3">Source</th><th scope="col" class="px-5 py-3">Sessions</th><th scope="col" class="px-5 py-3">Orders</th><th scope="col" class="px-5 py-3">Conversion rate</th></tr></thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($analytics['top_referrers'] as $referrer)
                            <tr><th scope="row" class="px-5 py-3 font-medium">{{ $referrer['source'] }}</th><td class="px-5 py-3 tabular-nums">{{ number_format($referrer['sessions']) }}</td><td class="px-5 py-3 tabular-nums">{{ number_format($referrer['orders']) }}</td><td class="px-5 py-3 tabular-nums">{{ number_format($referrer['conversion_rate'], 2) }}%</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-5 py-12 text-center text-sm text-zinc-500">Traffic source reports will appear when storefront visits are recorded.</div>
        @endif
    </section>
</div>
