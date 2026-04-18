@php
    $store = app('current_store');
@endphp
<header class="border-b bg-white dark:border-zinc-800 dark:bg-zinc-950">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="{{ route('storefront.home') }}" class="text-lg font-semibold">{{ $store->name }}</a>
        <nav class="flex items-center gap-4 text-sm">
            @if (Route::has('storefront.collections.index'))
                <a href="{{ route('storefront.collections.index') }}" class="hover:underline">Collections</a>
            @endif
            @if (Route::has('storefront.search.index'))
                <a href="{{ route('storefront.search.index') }}" class="hover:underline">Search</a>
            @endif
            @auth('customer')
                <a href="{{ route('account.dashboard') }}" class="hover:underline">Account</a>
            @else
                <a href="{{ route('account.login') }}" class="hover:underline">Sign in</a>
            @endauth
            @if (Route::has('storefront.cart.show'))
                <a href="{{ route('storefront.cart.show') }}" class="rounded-md border px-3 py-1 hover:bg-zinc-50 dark:hover:bg-zinc-800" data-testid="cart-link">
                    Cart
                </a>
            @endif
        </nav>
    </div>
</header>
