<div>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Discounts</h2>
        <flux:button variant="primary" href="{{ route('admin.discounts.create') }}">Create discount</flux:button>
    </div>

    <x-admin.data-table>
        <x-slot:head>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Usage</th>
                </tr>
        </x-slot:head>
        <x-slot:body>
                @forelse($discounts as $discount)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                        <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.discounts.edit', $discount) }}" class="text-gray-900 hover:underline dark:text-white">{{ $discount->code }}</a></td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($discount->value_type->value) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            @if($discount->value_type->value === 'percent')
                                {{ $discount->value_amount / 100 }}%
                            @elseif($discount->value_type->value === 'fixed')
                                ${{ number_format($discount->value_amount / 100, 2) }}
                            @else
                                Free shipping
                            @endif
                        </td>
                        <td class="px-6 py-4"><flux:badge size="sm" :variant="$discount->status->value === 'active' ? 'primary' : 'outline'">{{ ucfirst($discount->status->value) }}</flux:badge></td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $discount->usage_count }}{{ $discount->usage_limit ? '/' . $discount->usage_limit : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No discounts yet.</td></tr>
                @endforelse
        </x-slot:body>
    </x-admin.data-table>
    <div class="mt-4">{{ $discounts->links() }}</div>
</div>
