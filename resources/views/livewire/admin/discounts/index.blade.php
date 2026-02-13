<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <a href="{{ route('admin.discounts.create') }}">
            <flux:button variant="primary">Create Discount</flux:button>
        </a>
    </div>

    <div class="flex items-center gap-4">
        <flux:select wire:model.live="statusFilter">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="expired">Expired</option>
            <option value="disabled">Disabled</option>
        </flux:select>
        <flux:select wire:model.live="typeFilter">
            <option value="">All Types</option>
            <option value="code">Code</option>
            <option value="automatic">Automatic</option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Code</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Value</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Used</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($discounts as $discount)
                    <tr wire:key="discount-{{ $discount->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.discounts.edit', $discount) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $discount->code }}</a>
                        </td>
                        <td class="px-4 py-3">{{ ucfirst($discount->type->value) }}</td>
                        <td class="px-4 py-3">
                            @if ($discount->value_type->value === 'percent')
                                {{ $discount->value_amount / 100 }}%
                            @else
                                ${{ number_format($discount->value_amount / 100, 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($discount->status->value) }}</flux:badge></td>
                        <td class="px-4 py-3">{{ $discount->usage_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $discounts->links() }}</div>
</div>
