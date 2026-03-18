<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Customers') }}</flux:heading>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email...') }}" icon="magnifying-glass" />
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        @if($this->customers->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Name') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Email') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Orders') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Joined') }}</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50">
                    @foreach($this->customers as $customer)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="p-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-accent hover:underline" wire:navigate>
                                    {{ $customer->name ?? '-' }}
                                </a>
                            </td>
                            <td class="p-3">{{ $customer->email }}</td>
                            <td class="p-3">{{ $customer->orders_count }}</td>
                            <td class="p-3 text-zinc-500">{{ $customer->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $this->customers->links() }}</div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="users" class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No customers yet') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Customers will appear here when they create accounts.') }}</flux:text>
            </div>
        @endif
    </div>
</div>
