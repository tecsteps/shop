<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.discounts.create') }}" wire:navigate>
            <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
            Create discount
        </flux:button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-40">
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="disabled">Disabled</option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm" wire:loading.class="opacity-50" wire:target="search,statusFilter">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Code</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Value</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Usage</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Dates</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($discounts as $discount)
                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="disc-{{ $discount->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate class="font-medium text-gray-900 hover:text-blue-600 dark:text-white">
                                {{ $discount->code ?? 'Automatic' }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm">{{ ucfirst($discount->type->value) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            @if ($discount->value_type === \App\Enums\DiscountValueType::Percent)
                                {{ $discount->value_amount }}%
                            @elseif ($discount->value_type === \App\Enums\DiscountValueType::Fixed)
                                ${{ number_format($discount->value_amount / 100, 2) }}
                            @else
                                Free shipping
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match($discount->status->value) {
                                'active' => 'green',
                                'expired' => 'red',
                                default => 'zinc',
                            }" size="sm">
                                {{ ucfirst($discount->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $discount->starts_at?->format('M j, Y') }}
                            @if ($discount->ends_at) - {{ $discount->ends_at->format('M j, Y') }} @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">No discounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $discounts->links() }}</div>
</div>
