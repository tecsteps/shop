<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Account'],
    ]" />

    <div class="mt-8">
        <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Orders</h1>

        <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            @forelse ($orders as $order)
                <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="grid gap-3 border-b border-zinc-200 px-5 py-4 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                    <div>
                        <p class="font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <flux:badge>{{ $order->financial_status->value }}</flux:badge>
                        <flux:badge>{{ $order->fulfillment_status->value }}</flux:badge>
                    </div>
                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="justify-start sm:justify-end" />
                </a>
            @empty
                <div class="p-10 text-center">
                    <flux:icon name="shopping-bag" class="mx-auto size-10 text-zinc-400 dark:text-zinc-600" />
                    <p class="mt-4 font-medium text-zinc-950 dark:text-white">No orders yet</p>
                    <flux:button :href="route('collections.index')" wire:navigate variant="primary" class="mt-6">
                        Browse products
                    </flux:button>
                </div>
            @endforelse
        </div>
    </div>
</section>
