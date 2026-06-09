@php
    $cardClasses = 'group flex flex-col gap-2 rounded-2xl border border-zinc-200 p-6 transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-800 dark:hover:border-zinc-700';
@endphp

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
        {{ __('Welcome back, :name!', ['name' => $customer->name]) }}
    </h1>

    <x-storefront.account-nav current="dashboard" class="mt-6" />

    {{-- Quick links --}}
    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('storefront.account.orders.index') }}" class="{{ $cardClasses }}">
            <svg class="size-6 text-zinc-400 transition group-hover:text-(--sf-primary,#2563eb) dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Order history') }}</span>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('View all your orders') }}</span>
        </a>

        <a href="{{ route('storefront.account.addresses.index') }}" class="{{ $cardClasses }}">
            <svg class="size-6 text-zinc-400 transition group-hover:text-(--sf-primary,#2563eb) dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
            <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Addresses') }}</span>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Manage your addresses') }}</span>
        </a>

        <form method="POST" action="{{ route('storefront.account.logout') }}" class="contents">
            @csrf
            <button type="submit" class="{{ $cardClasses }} cursor-pointer text-left" data-test="customer-logout-card">
                <svg class="size-6 text-zinc-400 transition group-hover:text-(--sf-primary,#2563eb) dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Log out') }}</span>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('End your session securely') }}</span>
            </button>
        </form>
    </div>

    {{-- Recent orders --}}
    <section class="mt-10" aria-labelledby="recent-orders-heading">
        <div class="flex items-center justify-between gap-4">
            <h2 id="recent-orders-heading" class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Recent Orders') }}</h2>
            @if ($recentOrders->isNotEmpty())
                <a
                    href="{{ route('storefront.account.orders.index') }}"
                    class="rounded text-sm font-medium text-blue-600 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-400"
                >
                    {{ __('View all') }}
                </a>
            @endif
        </div>

        @if ($recentOrders->isEmpty())
            <p class="mt-4 rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                {{ __("You haven't placed any orders yet.") }}
            </p>
        @else
            <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Order') }}</th>
                            <th scope="col" class="hidden px-4 py-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase sm:table-cell dark:text-zinc-400">{{ __('Date') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Total') }}</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($recentOrders as $order)
                            <tr wire:key="recent-order-{{ $order->getKey() }}">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</td>
                                <td class="hidden px-4 py-3 text-zinc-600 sm:table-cell dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-3"><x-storefront.order-status-badge :status="$order->status" /></td>
                                <td class="px-4 py-3 text-right"><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" class="text-sm" /></td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}"
                                        class="rounded text-sm font-medium text-blue-600 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-400"
                                    >
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Profile --}}
    <section class="mt-10 max-w-xl" aria-labelledby="profile-heading">
        <h2 id="profile-heading" class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Profile') }}</h2>

        @if (session('profile-updated'))
            <p class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-950/50 dark:text-green-400" role="status">
                {{ session('profile-updated') }}
            </p>
        @endif

        <form wire:submit="updateProfile" class="mt-4 space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800">
            <div>
                <label for="profile-name" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Name') }} <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <input
                    id="profile-name"
                    type="text"
                    wire:model="name"
                    required
                    autocomplete="name"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    @error('name') aria-invalid="true" aria-describedby="profile-name-error" @enderror
                />
                @error('name')
                    <p id="profile-name-error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <p class="mb-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Email') }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $customer->email }}</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <input
                    type="checkbox"
                    wire:model="marketingOptIn"
                    class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-900"
                />
                {{ __('Subscribe to marketing emails') }}
            </label>

            <button
                type="submit"
                class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                style="background-color: var(--sf-primary, #2563eb);"
                data-test="profile-save-button"
            >
                <span wire:loading.remove wire:target="updateProfile">{{ __('Save changes') }}</span>
                <span wire:loading wire:target="updateProfile">{{ __('Saving...') }}</span>
            </button>
        </form>
    </section>
</div>
