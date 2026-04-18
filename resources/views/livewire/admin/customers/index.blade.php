<div class="space-y-4">
    <flux:heading size="xl">Customers</flux:heading>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by email or name..." />
    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Email</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Marketing</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr wire:key="customer-{{ $customer->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="p-3"><a class="text-sky-600 hover:underline" href="{{ route('admin.customers.show', $customer) }}" wire:navigate>{{ $customer->email }}</a></td>
                        <td class="p-3">{{ $customer->fullName() }}</td>
                        <td class="p-3">{{ $customer->accepts_marketing ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-zinc-500" colspan="3">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $customers->links() }}</div>
</div>
