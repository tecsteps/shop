<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ $customer->name ?? $customer->email }}</flux:heading>

        @can('update', $customer)
            <flux:button variant="primary" wire:click="openEditModal">Edit customer</flux:button>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Customer info card --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Customer info</flux:heading>
                <flux:separator class="my-4" />
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">Name</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $customer->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">Email</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $customer->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">Member since</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $customer->created_at?->format('M j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">Marketing</dt>
                        <dd>
                            <flux:badge size="sm" :color="$customer->marketing_opt_in ? 'green' : 'zinc'">
                                {{ $customer->marketing_opt_in ? 'Opted in' : 'Opted out' }}
                            </flux:badge>
                        </dd>
                    </div>
                </dl>

                {{-- Stats --}}
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Orders</flux:text>
                        <flux:heading size="lg">{{ $ordersCount }}</flux:heading>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Total spent</flux:text>
                        <flux:heading size="lg">{{ \App\Support\Money::format($totalSpent, $currency) }}</flux:heading>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Avg. order value</flux:text>
                        <flux:heading size="lg">{{ \App\Support\Money::format($averageOrderValue, $currency) }}</flux:heading>
                    </div>
                </div>
            </div>

            {{-- Order history --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Order history</flux:heading>
                <flux:separator class="my-4" />
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" wire:loading.class="opacity-50">
                        <thead>
                            <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                <th class="px-4 py-2 font-medium">Order</th>
                                <th class="px-4 py-2 font-medium">Date</th>
                                <th class="px-4 py-2 font-medium">Payment</th>
                                <th class="px-4 py-2 font-medium">Fulfillment</th>
                                <th class="px-4 py-2 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($orders as $order)
                                <tr wire:key="order-{{ $order->id }}">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $order->placed_at?->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <flux:badge size="sm" :color="match ($order->financial_status) {
                                            \App\Enums\FinancialStatus::Paid => 'green',
                                            \App\Enums\FinancialStatus::PartiallyRefunded, \App\Enums\FinancialStatus::Refunded => 'yellow',
                                            \App\Enums\FinancialStatus::Voided => 'red',
                                            default => 'zinc',
                                        }">{{ Str::headline($order->financial_status->value) }}</flux:badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:badge size="sm" :color="match ($order->fulfillment_status) {
                                            \App\Enums\FulfillmentOrderStatus::Fulfilled => 'green',
                                            \App\Enums\FulfillmentOrderStatus::Partial => 'yellow',
                                            default => 'zinc',
                                        }">{{ Str::headline($order->fulfillment_status->value) }}</flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $order->formattedTotal() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        This customer has not placed any orders yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $orders->links() }}
            </div>
        </div>

        {{-- Right column: addresses --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Addresses</flux:heading>
                <flux:separator class="my-4" />
                <div class="space-y-4">
                    @forelse ($customer->addresses as $address)
                        @php($fields = $address->address_json ?? [])
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="address-{{ $address->id }}">
                            <div class="flex items-center gap-2">
                                <flux:text class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $address->label ?? 'Address' }}</flux:text>
                                @if ($address->is_default)
                                    <flux:badge size="sm" color="green">Default</flux:badge>
                                @endif
                            </div>
                            <address class="mt-1 text-sm not-italic text-zinc-600 dark:text-zinc-300">
                                {{ trim(($fields['first_name'] ?? '').' '.($fields['last_name'] ?? '')) }}<br>
                                {{ $fields['address1'] ?? '' }}<br>
                                @if (! empty($fields['address2'])){{ $fields['address2'] }}<br>@endif
                                {{ $fields['city'] ?? '' }}{{ ! empty($fields['province']) ? ', '.$fields['province'] : '' }} {{ $fields['postal_code'] ?? '' }}<br>
                                {{ $fields['country'] ?? '' }}
                            </address>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500 dark:text-zinc-400">No saved addresses.</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Edit customer modal --}}
    <flux:modal wire:model="showEditModal" name="edit-customer" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Edit customer</flux:heading>

            <flux:field>
                <flux:label for="customerName">Name</flux:label>
                <flux:input id="customerName" wire:model.blur="name" placeholder="Jane Smith" />
                <flux:error name="name" />
            </flux:field>

            <flux:checkbox wire:model.blur="marketingOptIn" label="Subscribed to marketing emails" />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showEditModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveCustomer" wire:loading.attr="disabled" wire:target="saveCustomer">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
