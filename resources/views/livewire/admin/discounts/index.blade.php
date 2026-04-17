<div>
    <x-slot:title>Discounts</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button href="{{ route('admin.discounts.create') }}" variant="primary" wire:navigate>
            Add discount
        </flux:button>
    </div>

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code..." icon="magnifying-glass" class="sm:max-w-xs" />
        <flux:select wire:model.live="statusFilter" class="sm:w-40">
            <option value="all">All statuses</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="disabled">Disabled</option>
        </flux:select>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Usage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($discounts as $discount)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <a href="{{ route('admin.discounts.edit', $discount) }}" class="font-medium text-blue-600 hover:underline" wire:navigate>
                                {{ $discount->code }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ ucfirst($discount->value_type->value) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            @if ($discount->value_type->value === 'percent')
                                {{ $discount->value_amount / 100 }}%
                            @elseif ($discount->value_type->value === 'free_shipping')
                                Free shipping
                            @else
                                {{ number_format($discount->value_amount / 100, 2) }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="match($discount->status->value) { 'active' => 'green', 'draft' => 'yellow', 'expired' => 'zinc', 'disabled' => 'red', default => 'zinc' }">
                                {{ ucfirst($discount->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $discount->usage_count ?? 0 }}{{ $discount->usage_limit ? ' / ' . $discount->usage_limit : '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No discounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $discounts->links() }}
    </div>
</div>
