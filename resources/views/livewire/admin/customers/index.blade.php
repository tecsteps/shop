<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Customers')]]" />

    <flux:heading size="xl" level="1">{{ __('Customers') }}</flux:heading>

    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('Search by name or email...')"
        class="max-w-xs"
        data-test="customer-search"
    />

    <x-admin.card class="!p-0">
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-2.5">{{ __('Name') }}</th>
                        <th class="px-4 py-2.5">{{ __('Email') }}</th>
                        <th class="px-4 py-2.5 text-right">{{ __('Orders') }}</th>
                        <th class="px-4 py-2.5 text-right">{{ __('Total spent') }}</th>
                        <th class="px-4 py-2.5">{{ __('Created') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $customer->name ?: __('(no name)') }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->email }}</td>
                            <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3 text-right font-medium text-zinc-800 dark:text-zinc-200">
                                {{ \App\Support\Storefront\PriceFormatter::format((int) ($customer->orders_sum_total_amount ?? 0), $currentStore->default_currency ?? 'EUR') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->created_at?->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <flux:text>{{ __('No customers found.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->customers->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->customers->links() }}
            </div>
        @endif
    </x-admin.card>
</div>
