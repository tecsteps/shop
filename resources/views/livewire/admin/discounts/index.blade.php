<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button href="{{ route('admin.discounts.create') }}" variant="primary">New discount</flux:button>
    </div>
    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search discount codes..." icon="magnifying-glass" />
    </div>
    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Value</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Uses</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($discounts as $discount)
                    <tr wire:key="discount-{{ $discount->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                            <a href="{{ route('admin.discounts.edit', $discount) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $discount->code }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $discount->type->value ?? $discount->type)) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            @if(($discount->value_type->value ?? $discount->value_type) === 'percent')
                                {{ $discount->value }}%
                            @else
                                ${{ number_format($discount->value / 100, 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="($discount->status->value ?? $discount->status) === 'active' ? 'green' : 'zinc'">
                                {{ ucfirst($discount->status->value ?? $discount->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $discount->times_used ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No discounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $discounts->links() }}</div>
</div>
