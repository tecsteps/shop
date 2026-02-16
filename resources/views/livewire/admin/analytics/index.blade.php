<div>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Analytics</h2>
        <flux:select wire:model.live="period" class="w-40">
            <flux:select.option value="7">Last 7 days</flux:select.option>
            <flux:select.option value="30">Last 30 days</flux:select.option>
            <flux:select.option value="90">Last 90 days</flux:select.option>
        </flux:select>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenue</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($revenue / 100, 2) }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Orders</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $orderCount }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Order Value</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($averageOrderValue / 100, 2) }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Visits</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($visits) }}</p>
        </x-admin.card>
    </div>

    <x-admin.card>
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Conversion Funnel</h3>
        <div class="space-y-3">
            <div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Visits</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($visits) }}</span>
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-gray-200 dark:bg-zinc-700">
                    <div class="h-2 rounded-full bg-blue-500" style="width: 100%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Orders</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $orderCount }}</span>
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-gray-200 dark:bg-zinc-700">
                    <div class="h-2 rounded-full bg-green-500" style="width: {{ $conversionRate }}%"></div>
                </div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Conversion rate: {{ $conversionRate }}%</p>
        </div>
    </x-admin.card>
</div>
