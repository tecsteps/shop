<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button href="{{ route('admin.discounts.create') }}" wire:navigate variant="primary">
            Create Discount
        </flux:button>
    </div>

    {{-- Search and filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by discount code..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-full sm:w-40">
            <option value="all">All Statuses</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="expired">Expired</option>
            <option value="disabled">Disabled</option>
        </flux:select>
    </div>

    {{-- Discounts table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Code</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Value</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Usage</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Dates</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->discounts as $discount)
                        <tr wire:key="discount-{{ $discount->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate class="font-medium text-zinc-900 dark:text-white hover:underline">
                                    @if ($discount->type === \App\Enums\DiscountType::Automatic)
                                        Automatic
                                    @else
                                        {{ $discount->code }}
                                    @endif
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $discount->type === \App\Enums\DiscountType::Code ? 'zinc' : 'blue' }}" size="sm">
                                    {{ ucfirst($discount->type->value) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                @if ($discount->value_type === \App\Enums\DiscountValueType::Percent)
                                    {{ $discount->value_amount }}%
                                @elseif ($discount->value_type === \App\Enums\DiscountValueType::Fixed)
                                    {{ number_format($discount->value_amount / 100, 2) }}
                                @else
                                    Free shipping
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColor = match($discount->status) {
                                        \App\Enums\DiscountStatus::Active => 'green',
                                        \App\Enums\DiscountStatus::Expired => 'red',
                                        \App\Enums\DiscountStatus::Disabled => 'zinc',
                                        default => 'yellow',
                                    };
                                @endphp
                                <flux:badge color="{{ $statusColor }}" size="sm">
                                    {{ ucfirst($discount->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">
                                <div>{{ $discount->starts_at?->format('M j, Y') ?? '-' }}</div>
                                <div>{{ $discount->ends_at?->format('M j, Y') ?? 'No end' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon name="tag" class="size-12 text-zinc-300 dark:text-zinc-600" />
                                    <flux:heading size="md">No discounts yet</flux:heading>
                                    <flux:text class="text-zinc-500 dark:text-zinc-400">Create your first discount to get started.</flux:text>
                                    <flux:button href="{{ route('admin.discounts.create') }}" wire:navigate variant="primary">
                                        Create Discount
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->discounts->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->discounts->links() }}
            </div>
        @endif
    </div>
</div>
