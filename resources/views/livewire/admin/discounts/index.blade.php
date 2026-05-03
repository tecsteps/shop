<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Discounts</flux:heading>
            <flux:text>Codes, automatic offers, limits, and lifecycle status.</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('admin.discounts.create')" wire:navigate>
            Add discount
        </flux:button>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800 md:grid-cols-[1fr_14rem]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search codes" icon="magnifying-glass" />
            <flux:select wire:model.live="status">
                <option value="all">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950">
                    <tr>
                        <th class="px-5 py-3">Code</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Value</th>
                        <th class="px-5 py-3">Usage</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($discounts as $discount)
                        <tr wire:key="admin-discount-{{ $discount->id }}">
                            <td class="px-5 py-4 font-medium">{{ $discount->code ?? 'Automatic discount' }}</td>
                            <td class="px-5 py-4">{{ $discount->type->value }}</td>
                            <td class="px-5 py-4">{{ $discount->value_type->value }} · {{ $discount->value_amount }}</td>
                            <td class="px-5 py-4">{{ $discount->usage_count }}{{ $discount->usage_limit ? ' / '.$discount->usage_limit : '' }}</td>
                            <td class="px-5 py-4"><flux:badge>{{ $discount->status->value }}</flux:badge></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" :href="route('admin.discounts.edit', $discount)" wire:navigate>Edit</flux:button>
                                    <flux:button size="sm" wire:click="disable({{ $discount->id }})">Disable</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-zinc-500">No discounts match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            {{ $discounts->links() }}
        </div>
    </div>
</div>
