@php
    $customer = $customer ?? auth('customer')->user();
    $items = [
        ['label' => 'Dashboard', 'route' => 'account.dashboard', 'active' => request()->routeIs('account.dashboard')],
        ['label' => 'Order history', 'route' => 'account.orders.index', 'active' => request()->routeIs('account.orders.*')],
        ['label' => 'Addresses', 'route' => 'account.addresses.index', 'active' => request()->routeIs('account.addresses.index')],
    ];
@endphp

<nav aria-label="Account navigation" class="space-y-1">
    @foreach ($items as $item)
        <a
            href="{{ route($item['route']) }}"
            class="block rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white' }}"
        >
            {{ $item['label'] }}
        </a>
    @endforeach

    <form method="POST" action="{{ route('account.logout') }}" class="pt-2">
        @csrf
        <button
            type="submit"
            class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
        >
            Log out
        </button>
    </form>
</nav>
