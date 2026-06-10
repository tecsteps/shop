{{--
    Tab navigation shared by all customer account pages. `current` is one of
    "dashboard", "orders", or "addresses".
--}}
@props([
    'current' => 'dashboard',
])

@php
    $tabs = [
        'dashboard' => ['label' => __('My Account'), 'url' => route('storefront.account.index')],
        'orders' => ['label' => __('Orders'), 'url' => route('storefront.account.orders.index')],
        'addresses' => ['label' => __('Addresses'), 'url' => route('storefront.account.addresses.index')],
    ];

    $activeClasses = 'border-(--sf-primary,#2563eb) font-semibold text-zinc-900 dark:text-white';
    $inactiveClasses = 'border-transparent font-medium text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200';
@endphp

<nav {{ $attributes->class('border-b border-zinc-200 dark:border-zinc-800') }} aria-label="{{ __('Account navigation') }}">
    <div class="-mb-px flex items-center justify-between gap-4 overflow-x-auto">
        <ul class="flex items-center gap-6">
            @foreach ($tabs as $key => $tab)
                <li>
                    <a
                        href="{{ $tab['url'] }}"
                        @if ($current === $key) aria-current="page" @endif
                        class="inline-flex whitespace-nowrap border-b-2 px-1 py-3 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 {{ $current === $key ? $activeClasses : $inactiveClasses }}"
                    >
                        {{ $tab['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('storefront.account.logout') }}" class="shrink-0">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                data-test="customer-logout-button"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</nav>
