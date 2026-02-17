<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.discounts.create') }}" wire:navigate icon="plus">Create discount</flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="all">All status</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="expired">Expired</flux:select.option>
            <flux:select.option value="disabled">Disabled</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @if ($this->discounts->isEmpty())
            <div class="py-12 text-center">
                <flux:text class="text-zinc-400">No discounts found.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Code</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Value</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Usage</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Dates</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->discounts as $discount)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.discounts.edit', $discount) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                    {{ $discount->code ?? 'Automatic' }}
                                </a>
                            </td>
                            <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($discount->type->value) }}</flux:badge></td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                @if ($discount->value_type->value === 'percent')
                                    {{ $discount->value_amount }}%
                                @elseif ($discount->value_type->value === 'fixed')
                                    ${{ number_format($discount->value_amount / 100, 2) }}
                                @else
                                    Free shipping
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $sColor = match($discount->status->value) {
                                        'active' => 'green',
                                        'expired' => 'red',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge :color="$sColor" size="sm">{{ ucfirst($discount->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $discount->starts_at?->format('M j') }} - {{ $discount->ends_at?->format('M j') ?? 'No end' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">{{ $this->discounts->links() }}</div>
        @endif
    </div>
</div>
