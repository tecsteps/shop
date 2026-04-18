<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button href="{{ route('admin.discounts.create') }}" variant="primary" wire:navigate>New discount</flux:button>
    </div>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code..." />
    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Code</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Value</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($discounts as $discount)
                    <tr wire:key="discount-{{ $discount->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="p-3"><a class="text-sky-600 hover:underline" href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate>{{ $discount->code ?? '(automatic)' }}</a></td>
                        <td class="p-3">{{ $discount->value_type?->value }}</td>
                        <td class="p-3">{{ $discount->value_amount }}</td>
                        <td class="p-3">{{ $discount->status?->value }}</td>
                        <td class="p-3 text-right">
                            <flux:button size="xs" variant="danger" wire:click="delete({{ $discount->id }})" wire:confirm="Delete this discount?">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-zinc-500" colspan="5">No discounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $discounts->links() }}</div>
</div>
