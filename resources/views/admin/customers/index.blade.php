<div class="space-y-6">
    <x-admin.page-header title="Customers" description="Customer profiles, order history, and lifetime value." />
    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by name or email…" aria-label="Search customers" class="max-w-lg" />
    <x-admin.table-shell caption="Customers" loading-target="search">
        <x-slot:head><tr><th>Name</th><th>Email</th><th>Orders</th><th>Total spent</th><th>Customer since</th></tr></x-slot:head>
        @forelse($this->customers as $customer)<tr wire:key="customer-{{ $customer->id }}"><td><a href="{{ url('/admin/customers/'.$customer->id) }}" wire:navigate class="font-medium text-slate-950 hover:text-blue-700 dark:text-white">{{ $customer->name ?: 'Guest customer' }}</a></td><td>{{ $customer->email }}</td><td>{{ $customer->orders_count }}</td><td><x-admin.money :amount="$customer->orders_sum_total_amount ?? 0" :currency="$adminStore->default_currency" /></td><td class="whitespace-nowrap text-slate-500">{{ $customer->created_at->format('M j, Y') }}</td></tr>
        @empty<x-admin.table-empty colspan="5" title="No customers found" description="Customer records are created during registration and checkout." />@endforelse
        <x-slot:pagination>{{ $this->customers->links() }}</x-slot:pagination>
    </x-admin.table-shell>
</div>
