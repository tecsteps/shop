<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Discounts</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Discounts</flux:heading>
        @can('create', App\Models\Discount::class)
            <flux:button variant="primary" href="{{ route('admin.discounts.create') }}" wire:navigate>Create discount</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by code..." aria-label="Search discounts" class="max-w-xs flex-1" />
        <flux:select wire:model.live="statusFilter" aria-label="Filter by status" class="w-40">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="scheduled">Scheduled</flux:select.option>
            <flux:select.option value="expired">Expired</flux:select.option>
            <flux:select.option value="disabled">Disabled</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:loading.class="opacity-50">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Code</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Value</th>
                    <th class="px-4 py-3 font-medium">Usage</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Dates</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($discounts as $discount)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="discount-{{ $discount->getKey() }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.discounts.edit', $discount) }}" class="font-medium underline" wire:navigate>{{ $discount->code ?? 'Automatic' }}</a>
                        </td>
                        <td class="px-4 py-3"><flux:badge>{{ ucfirst($discount->type->value) }}</flux:badge></td>
                        <td class="px-4 py-3">
                            @if ($discount->value_type->value === 'percent')
                                {{ $discount->value_amount }}%
                            @elseif ($discount->value_type->value === 'fixed')
                                {{ $this->money($discount->value_amount) }}
                            @else
                                Free shipping
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $exhausted = $discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit;
                                $derived = $exhausted
                                    ? 'exhausted'
                                    : ($discount->starts_at && $discount->starts_at->isFuture() ? 'scheduled' : $discount->status->value);
                            @endphp
                            <flux:badge :color="match ($derived) { 'active' => 'green', 'expired' => 'red', 'scheduled' => 'yellow', 'exhausted' => 'red', default => 'zinc' }">{{ ucfirst($derived) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">{{ $discount->starts_at?->format('M j, Y') }} - {{ $discount->ends_at?->format('M j, Y') ?? 'No end' }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('delete', $discount)
                                <flux:button variant="ghost" size="sm" wire:click="confirmDelete({{ $discount->getKey() }})" aria-label="Delete discount">
                                    <flux:icon.trash class="size-4" variant="outline" />
                                </flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center"><flux:text variant="subtle">No discounts found.</flux:text></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $discounts->links() }}
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete this discount?</flux:heading>
        <flux:text class="mt-2">The discount will be permanently removed and can no longer be applied.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deleteDiscount">Delete</flux:button>
        </div>
    </flux:modal>
</div>
