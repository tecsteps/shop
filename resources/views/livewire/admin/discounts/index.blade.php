<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Discounts</flux:heading>
            <flux:text class="mt-1">Manage discount codes, automatic promotions, and eligibility rules.</flux:text>
        </div>

        @can('create', App\Models\Discount::class)
            <flux:button :href="route('admin.discounts.create')" wire:navigate variant="primary" icon="plus">
                Create discount
            </flux:button>
        @endcan
    </div>

    <div class="grid gap-3 lg:grid-cols-[1fr_180px_180px]">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search discounts..." aria-label="Search discounts" />

        <flux:select wire:model.live="statusFilter" aria-label="Status filter">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="scheduled">Scheduled</flux:select.option>
            <flux:select.option value="expired">Expired</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="typeFilter" aria-label="Type filter">
            <flux:select.option value="all">All types</flux:select.option>
            <flux:select.option value="code">Code</flux:select.option>
            <flux:select.option value="automatic">Automatic</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Value</th>
                        <th class="px-4 py-3">Usage</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dates</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($discounts as $discount)
                        @php
                            $status = $this->effectiveStatus($discount);
                            $statusColor = $this->statusColor($status);
                            $typeColor = $discount->type->value === 'automatic' ? 'sky' : 'zinc';
                        @endphp

                        <tr wire:key="admin-discount-{{ $discount->getKey() }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.discounts.edit', $discount) }}" class="font-medium text-zinc-950 hover:underline dark:text-white" wire:navigate>
                                    {{ $discount->code ?: 'Automatic' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$typeColor">{{ Str::headline($discount->type->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">{{ $this->valueLabel($discount) }}</td>
                            <td class="px-4 py-3">{{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$statusColor">{{ Str::headline($status) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $discount->starts_at?->format('M j, Y') }}
                                <span class="text-zinc-400">-</span>
                                {{ $discount->ends_at?->format('M j, Y') ?? 'No end' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <div class="flex size-12 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="tag" class="size-6" />
                                    </div>
                                    <flux:heading size="lg">No discounts found</flux:heading>
                                    <flux:text>Discounts will appear here after creation.</flux:text>
                                    @can('create', App\Models\Discount::class)
                                        <flux:button :href="route('admin.discounts.create')" wire:navigate variant="primary">Create discount</flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $discounts->links() }}
</section>
