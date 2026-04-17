<div class="flex flex-col gap-8">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Hi, {{ $customer->name }}</flux:heading>
        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" size="sm">Sign out</flux:button>
        </form>
    </div>

    <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
        <flux:heading size="lg" class="mb-4">Recent orders</flux:heading>
        @if ($orders->isEmpty())
            <div class="rounded-lg bg-zinc-50 p-6 text-center text-zinc-500 dark:bg-zinc-800">
                You have no orders yet.
            </div>
        @else
            <div class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($orders as $order)
                    <div class="flex items-center justify-between py-3" data-testid="account-order">
                        <div>
                            <div class="font-medium">{{ $order->order_number }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->placed_at?->format('F j, Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->financial_status->value }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
