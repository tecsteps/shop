@php
    use App\Support\Storefront\PriceFormatter;
@endphp

<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
        {{ __('Welcome back, :name!', ['name' => $customer->name]) }}
    </h1>

    {{-- Quick links. --}}
    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('account.orders.index') }}" wire:navigate
           class="rounded-xl border border-zinc-200 p-5 transition hover:shadow-md dark:border-zinc-800">
            <flux:icon.shopping-bag class="size-6 text-zinc-500" />
            <h2 class="mt-3 font-semibold text-zinc-900 dark:text-white">{{ __('Order history') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('View all your orders') }}</p>
        </a>
        <a href="{{ route('account.addresses') }}" wire:navigate
           class="rounded-xl border border-zinc-200 p-5 transition hover:shadow-md dark:border-zinc-800">
            <flux:icon.map-pin class="size-6 text-zinc-500" />
            <h2 class="mt-3 font-semibold text-zinc-900 dark:text-white">{{ __('Addresses') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Manage your addresses') }}</p>
        </a>
        <form method="POST" action="{{ route('account.logout') }}"
              class="rounded-xl border border-zinc-200 p-5 text-left transition hover:shadow-md dark:border-zinc-800">
            @csrf
            <flux:icon.arrow-right-start-on-rectangle class="size-6 text-zinc-500" />
            <h2 class="mt-3 font-semibold text-zinc-900 dark:text-white">{{ __('Log out') }}</h2>
            <button type="submit" class="text-sm text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Sign out of your account') }}</button>
        </form>
    </div>

    {{-- Recent orders. --}}
    <h2 class="mt-12 text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Recent orders') }}</h2>
    @if ($recentOrders->isEmpty())
        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __("You haven't placed any orders yet.") }}</p>
    @else
        <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                        <th scope="col" class="px-4 py-3">{{ __('Order') }}</th>
                        <th scope="col" class="px-4 py-3">{{ __('Date') }}</th>
                        <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3">{{ __('Total') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Action') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-3"><x-storefront::badge :text="ucfirst($order->status->value)" /></td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-white">{{ PriceFormatter::format($order->total_amount, $order->currency) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" wire:navigate
                                   class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
