<div>
    {{-- Breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}">Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Orders</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page heading --}}
    <div class="mb-6">
        <flux:heading size="xl">Orders</flux:heading>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by order number or customer name..." icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="status" placeholder="All statuses">
                <option value="">All statuses</option>
                @foreach ($orderStatuses as $orderStatus)
                    <option value="{{ $orderStatus->value }}">{{ ucfirst($orderStatus->name) }}</option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="financialStatus" placeholder="All financial">
                <option value="">All financial</option>
                @foreach ($financialStatuses as $fStatus)
                    <option value="{{ $fStatus->value }}">{{ str_replace('_', ' ', ucfirst($fStatus->value)) }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Order #</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Customer</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Financial Status</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Fulfillment</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="order-{{ $order->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                #{{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                            {{ $order->customer?->full_name ?? 'Guest' }}
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">
                            {{ \App\Support\MoneyFormatter::format($order->total_amount) }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $financialColor = match($order->financial_status) {
                                    \App\Enums\FinancialStatus::Paid => 'green',
                                    \App\Enums\FinancialStatus::Pending => 'yellow',
                                    \App\Enums\FinancialStatus::Authorized => 'blue',
                                    \App\Enums\FinancialStatus::PartiallyRefunded => 'orange',
                                    \App\Enums\FinancialStatus::Refunded => 'red',
                                    \App\Enums\FinancialStatus::Voided => 'zinc',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$financialColor">
                                {{ str_replace('_', ' ', ucfirst($order->financial_status->value)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $fulfillmentColor = match($order->fulfillment_status) {
                                    \App\Enums\FulfillmentStatus::Fulfilled => 'green',
                                    \App\Enums\FulfillmentStatus::Partial => 'yellow',
                                    \App\Enums\FulfillmentStatus::Unfulfilled => 'zinc',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$fulfillmentColor">
                                {{ ucfirst($order->fulfillment_status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $order->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($orders->hasPages())
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>
