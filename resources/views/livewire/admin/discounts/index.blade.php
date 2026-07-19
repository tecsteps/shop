<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Discounts</flux:heading>

        @can('create', \App\Models\Discount::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.discounts.create')" wire:navigate>Create discount</flux:button>
        @endcan
    </div>

    @if (! $hasDiscounts)
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="tag" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">Create your first discount</flux:heading>
            <flux:text class="mt-1">Offer percentage, fixed amount, or free shipping discounts.</flux:text>
            @can('create', \App\Models\Discount::class)
                <flux:button variant="primary" class="mt-6" :href="route('admin.discounts.create')" wire:navigate>Create discount</flux:button>
            @endcan
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search by code..." class="w-full sm:w-72" aria-label="Search discounts" />

            <flux:select wire:model.live="statusFilter" class="w-40" aria-label="Status filter">
                <flux:select.option value="all">All statuses</flux:select.option>
                <flux:select.option value="draft">Draft</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="scheduled">Scheduled</flux:select.option>
                <flux:select.option value="expired">Expired</flux:select.option>
                <flux:select.option value="disabled">Disabled</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="typeFilter" class="w-40" aria-label="Type filter">
                <flux:select.option value="all">All types</flux:select.option>
                <flux:select.option value="code">Code</flux:select.option>
                <flux:select.option value="automatic">Automatic</flux:select.option>
            </flux:select>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[860px] text-left text-sm" wire:loading.class="opacity-50">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-3 font-medium">Code</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Value</th>
                        <th class="px-4 py-3 font-medium">Usage</th>
                        <th class="px-4 py-3 font-medium">Dates</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($discounts as $discount)
                        @php($status = $this->displayStatus($discount))
                        <tr wire:key="discount-{{ $discount->id }}">
                            <td class="px-4 py-3">
                                @can('update', $discount)
                                    <a href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">
                                        {{ $discount->code ?? 'Automatic' }}
                                    </a>
                                @else
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $discount->code ?? 'Automatic' }}</span>
                                @endcan
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="$discount->type === \App\Enums\DiscountType::Automatic ? 'blue' : 'zinc'">
                                    {{ $discount->type === \App\Enums\DiscountType::Automatic ? 'Automatic' : 'Code' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $this->displayValue($discount, $currency) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'Unlimited' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $discount->starts_at?->format('M j, Y') ?? '—' }} → {{ $discount->ends_at?->format('M j, Y') ?? 'No end' }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match ($status) {
                                    'active' => 'green',
                                    'scheduled' => 'yellow',
                                    'expired' => 'red',
                                    default => 'zinc',
                                }">{{ ucfirst($status) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $discount)
                                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.discounts.edit', $discount)" wire:navigate aria-label="Edit {{ $discount->code ?? 'discount' }}" />
                                        @if ($discount->status === \App\Enums\DiscountStatus::Active)
                                            <flux:button size="sm" variant="ghost" wire:click="disable({{ $discount->id }})">Disable</flux:button>
                                        @elseif (in_array($discount->status, [\App\Enums\DiscountStatus::Disabled, \App\Enums\DiscountStatus::Draft], true))
                                            <flux:button size="sm" variant="ghost" wire:click="enable({{ $discount->id }})">Enable</flux:button>
                                        @endif
                                    @endcan
                                    @can('delete', $discount)
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $discount->id }})" aria-label="Delete {{ $discount->code ?? 'discount' }}" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No discounts match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $discounts->links() }}
    @endif

    {{-- Delete confirmation modal (spec 03 §19.3) --}}
    <flux:modal wire:model="confirmingDelete" name="confirm-delete-discount" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete this discount?</flux:heading>
            <flux:text>The discount will be permanently removed. Orders that already used it are not affected.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
