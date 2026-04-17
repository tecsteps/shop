@props(['title' => null])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | '.$currentStore->name : $currentStore->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-full bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-4">
            <a href="{{ route('storefront.home') }}" class="text-lg font-bold tracking-tight" wire:navigate>
                {{ $currentStore->name }}
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
                <a href="{{ route('storefront.home') }}" class="hover:text-zinc-900 dark:hover:text-white" wire:navigate>Home</a>
                <a href="{{ route('storefront.collections.index') }}" class="hover:text-zinc-900 dark:hover:text-white" wire:navigate>Shop</a>
                <a href="{{ route('storefront.search') }}" class="hover:text-zinc-900 dark:hover:text-white" wire:navigate>Search</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth('customer')
                    <a href="{{ route('storefront.account.dashboard') }}" class="text-sm font-medium hover:underline" wire:navigate>
                        {{ auth('customer')->user()->name }}
                    </a>
                @else
                    <a href="{{ route('storefront.account.login') }}" class="text-sm font-medium hover:underline" wire:navigate>
                        Sign in
                    </a>
                @endauth

                <a
                    href="{{ route('storefront.cart.show') }}"
                    class="relative inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900"
                    wire:navigate
                    data-testid="cart-link"
                >
                    Cart
                    <livewire:storefront.cart-badge />
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-zinc-200 bg-zinc-50 py-10 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-6xl px-4">
            &copy; {{ date('Y') }} {{ $currentStore->name }}. Powered by Shop.
        </div>
    </footer>
    @fluxScripts
    @livewireScripts
</body>
</html>
