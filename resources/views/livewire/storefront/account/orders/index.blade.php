@php
    use App\Support\Storefront\PriceFormatter;
@endphp

<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Account'), 'url' => route('account.dashboard')],
        ['label' => __('Orders')],
    ]" />

    <h1 class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{{ __('Order history') }}</h1>

    @if ($orders->isEmpty())
        <div class="mt-12 rounded-xl border border-dashed border-zinc-300 py-16 text-center dark:border-zinc-700">
            <p class="text-zinc-500 dark:text-zinc-400">{{ __("You haven't placed any orders yet.") }}</p>
            <a href="{{ route('storefront.home') }}" wire:navigate
               class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ __('Start shopping') }}</a>
        </div>
    @else
        {{-- Desktop table. --}}
        <div class="mt-6 hidden overflow-hidden rounded-xl border border-zinc-200 sm:block dark:border-zinc-800">
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
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                                <a href="{{ route('account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" wire:navigate class="hover:underline">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-3"><x-storefront::badge :text="ucfirst($order->status->value)" /></td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-white">{{ PriceFormatter::format($order->total_amount, $order->currency) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" wire:navigate class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards. --}}
        <div class="mt-6 space-y-3 sm:hidden">
            @foreach ($orders as $order)
                <a href="{{ route('account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" wire:navigate
                   class="block rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</span>
                        <x-storefront::badge :text="ucfirst($order->status->value)" />
                    </div>
                    <div class="mt-1 flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
                        <span>{{ $order->placed_at?->format('M j, Y') }}</span>
                        <span>{{ PriceFormatter::format($order->total_amount, $order->currency) }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            <x-storefront::pagination :paginator="$orders" />
        </div>
    @endif
</div>
