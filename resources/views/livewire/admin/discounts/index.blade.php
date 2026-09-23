<div>
    @include('livewire.admin.components.page-heading', [
        'title' => 'Discounts',
        'description' => 'Create and manage codes and automatic promotions.',
        'actionLabel' => $canManage ? 'Create discount' : null,
        'actionHref' => $canManage ? route('admin.discounts.create') : null,
    ])

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="discount-list-heading">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <h2 id="discount-list-heading" class="font-semibold">Discounts</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem]">
                <div>
                    <label for="discount-search" class="sr-only">Search discount codes</label>
                    <input id="discount-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search codes" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                <div>
                    <label for="discount-status-filter" class="sr-only">Filter discounts by status</label>
                    <select id="discount-status-filter" wire:model.live="statusFilter" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="all">All statuses</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                </div>
            </div>
        </div>

        @if ($discounts->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950/50 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-5 py-3">Promotion</th>
                            <th scope="col" class="px-4 py-3">Activation</th>
                            <th scope="col" class="px-4 py-3">Value</th>
                            <th scope="col" class="px-4 py-3">Usage</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Dates</th>
                            @if ($canManage)<th scope="col" class="px-5 py-3 text-right">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($discounts as $discount)
                            <tr wire:key="admin-discount-{{ $discount->id }}">
                                <th scope="row" class="px-5 py-4">
                                    <span class="block font-medium">{{ $discount->title }}</span>
                                    <span class="mt-1 block font-mono text-xs text-zinc-500">{{ $discount->code ?: 'Automatic' }}</span>
                                </th>
                                <td class="px-4 py-4">{{ ($discount->rules_json['activation_method'] ?? ($discount->code ? 'code' : 'automatic')) === 'automatic' ? 'Automatic' : 'Code' }}</td>
                                <td class="px-4 py-4 tabular-nums">
                                    @if ($discount->type === 'percentage')
                                        {{ $discount->value }}%
                                    @elseif ($discount->type === 'fixed_amount')
                                        {{ number_format($discount->value / 100, 2) }} {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}
                                    @else
                                        Free shipping
                                    @endif
                                </td>
                                <td class="px-4 py-4 tabular-nums">{{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}</td>
                                <td class="px-4 py-4">
                                    @php($discountStatus = ! $discount->is_active ? 'inactive' : ($discount->ends_at?->isPast() ? 'expired' : ($discount->starts_at?->isFuture() ? 'scheduled' : 'active')))
                                    @include('livewire.admin.components.status-badge', ['status' => $discountStatus])
                                </td>
                                <td class="px-4 py-4 text-xs leading-5 text-zinc-600 dark:text-zinc-400">{{ $discount->starts_at?->format('M j, Y H:i') }}<br>{{ $discount->ends_at?->format('M j, Y H:i') ?? 'No end date' }}</td>
                                @if ($canManage)
                                    <td class="px-5 py-4 text-right">
                                        <div class="inline-flex flex-wrap justify-end gap-1">
                                            <a href="{{ route('admin.discounts.edit', ['discount' => $discount->id]) }}" wire:navigate class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-medium text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-950/40">Edit</a>
                                            <button type="button" wire:click="toggle({{ $discount->id }})" class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ $discount->is_active ? 'Deactivate' : 'Activate' }}</button>
                                            <button type="button" wire:click="delete({{ $discount->id }})" wire:confirm="Delete {{ $discount->title }}?" class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40">Delete</button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <h3 class="font-semibold">No discounts yet</h3>
                <p class="mt-1 text-sm text-zinc-500">Create a code or automatic promotion for your customers.</p>
                @if ($canManage)<a href="{{ route('admin.discounts.create') }}" wire:navigate class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Create discount</a>@endif
            </div>
        @endif

        @if ($discounts->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">{{ $discounts->links() }}</div>
        @endif
    </section>
</div>
