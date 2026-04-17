<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button variant="primary" href="{{ url('/admin/discounts/create') }}">Create discount</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code..." icon="magnifying-glass" class="sm:max-w-xs" />

    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2">Code</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Value</th>
                    <th class="px-4 py-2">Usage</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Dates</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($discounts as $discount)
                    <tr wire:key="discount-{{ $discount->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-4 py-2">
                            <a href="{{ url('/admin/discounts/'.$discount->id.'/edit') }}" class="font-medium hover:underline">{{ $discount->code ?? 'Automatic' }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $discount->type->value }}</td>
                        <td class="px-4 py-2">
                            @if ($discount->value_type->value === 'percent')
                                {{ $discount->value_amount }}%
                            @elseif ($discount->value_type->value === 'fixed')
                                {{ number_format($discount->value_amount / 100, 2) }}
                            @else
                                Free shipping
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}</td>
                        <td class="px-4 py-2"><flux:badge size="sm">{{ $discount->status->value }}</flux:badge></td>
                        <td class="px-4 py-2 text-xs text-neutral-500">
                            {{ optional($discount->starts_at)->format('M j') }}
                            -
                            {{ optional($discount->ends_at)->format('M j') ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-neutral-500">No discounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $discounts->links() }}</div>
</div>
