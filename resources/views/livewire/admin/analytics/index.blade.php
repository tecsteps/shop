<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Analytics') }}</flux:heading>

        <flux:dropdown>
            <flux:button icon-trailing="chevron-down">
                {{ match($period) {
                    '7d' => __('Last 7 days'),
                    '30d' => __('Last 30 days'),
                    '90d' => __('Last 90 days'),
                    'custom' => __('Custom range'),
                    default => __('Last 30 days'),
                } }}
            </flux:button>
            <flux:menu>
                <flux:menu.item wire:click="$set('period', '7d')">{{ __('Last 7 days') }}</flux:menu.item>
                <flux:menu.item wire:click="$set('period', '30d')">{{ __('Last 30 days') }}</flux:menu.item>
                <flux:menu.item wire:click="$set('period', '90d')">{{ __('Last 90 days') }}</flux:menu.item>
                <flux:menu.item wire:click="$set('period', 'custom')">{{ __('Custom range') }}</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    @if($period === 'custom')
        <div class="flex gap-4 mb-6">
            <flux:input type="date" wire:model.live="customStart" label="{{ __('Start date') }}" />
            <flux:input type="date" wire:model.live="customEnd" label="{{ __('End date') }}" />
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Total Revenue') }}</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($totalRevenue / 100, 2) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Total Orders') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($totalOrders) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Avg Order Value') }}</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($aov / 100, 2) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Total Visits') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($totalVisits) }}</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Add-to-Cart Rate') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $addToCartRate }}%</flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <flux:text class="text-zinc-500">{{ __('Checkout Conversion') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $checkoutConversionRate }}%</flux:heading>
        </div>
    </div>

    {{-- Sales Chart --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
        <flux:heading size="lg" class="mb-4">{{ __('Daily Revenue') }}</flux:heading>

        @if(count($chartLabels) > 0)
            <div
                x-data="{
                    labels: @js($chartLabels),
                    data: @js($chartData),
                    init() {
                        this.renderChart();
                    },
                    renderChart() {
                        const canvas = this.$refs.chart;
                        const ctx = canvas.getContext('2d');
                        const width = canvas.width = canvas.parentElement.clientWidth;
                        const height = canvas.height = 250;
                        const padding = { top: 20, right: 20, bottom: 40, left: 60 };

                        ctx.clearRect(0, 0, width, height);

                        if (this.data.length === 0) return;

                        const maxVal = Math.max(...this.data, 1);
                        const chartWidth = width - padding.left - padding.right;
                        const chartHeight = height - padding.top - padding.bottom;

                        // Draw grid lines
                        ctx.strokeStyle = '#e4e4e7';
                        ctx.lineWidth = 0.5;
                        for (let i = 0; i <= 4; i++) {
                            const y = padding.top + (chartHeight / 4) * i;
                            ctx.beginPath();
                            ctx.moveTo(padding.left, y);
                            ctx.lineTo(width - padding.right, y);
                            ctx.stroke();

                            const val = maxVal - (maxVal / 4) * i;
                            ctx.fillStyle = '#71717a';
                            ctx.font = '11px sans-serif';
                            ctx.textAlign = 'right';
                            ctx.fillText('$' + val.toFixed(0), padding.left - 8, y + 4);
                        }

                        // Draw line
                        ctx.strokeStyle = '#6366f1';
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        this.data.forEach((val, i) => {
                            const x = padding.left + (chartWidth / (this.data.length - 1 || 1)) * i;
                            const y = padding.top + chartHeight - (val / maxVal) * chartHeight;
                            if (i === 0) ctx.moveTo(x, y);
                            else ctx.lineTo(x, y);
                        });
                        ctx.stroke();

                        // Draw x-axis labels (show every nth)
                        const step = Math.max(1, Math.floor(this.labels.length / 6));
                        ctx.fillStyle = '#71717a';
                        ctx.font = '11px sans-serif';
                        ctx.textAlign = 'center';
                        this.labels.forEach((label, i) => {
                            if (i % step === 0 || i === this.labels.length - 1) {
                                const x = padding.left + (chartWidth / (this.data.length - 1 || 1)) * i;
                                ctx.fillText(label.slice(5), x, height - 10);
                            }
                        });
                    }
                }"
            >
                <canvas x-ref="chart" class="w-full" style="height: 250px;"></canvas>
            </div>
        @else
            <div class="text-center py-12">
                <flux:icon name="chart-bar" class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:text class="mt-2 text-zinc-500">{{ __('No data available for the selected period.') }}</flux:text>
            </div>
        @endif
    </div>
</div>
