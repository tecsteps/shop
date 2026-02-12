<div>
    {{-- Breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}">Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Customers</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page heading --}}
    <div class="mb-6">
        <flux:heading size="xl">Customers</flux:heading>
    </div>

    {{-- Search --}}
    <div class="mb-6">
        <div class="max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Email</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Orders</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Total Spent</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="customer-{{ $customer->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $customer->full_name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $customer->email }}
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">
                            {{ $customer->orders_count }}
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">
                            {{ \App\Support\MoneyFormatter::format((int) ($customer->orders_sum_total_amount ?? 0)) }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $customer->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($customers->hasPages())
        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    @endif
</div>
