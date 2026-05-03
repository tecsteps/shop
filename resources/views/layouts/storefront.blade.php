<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
        @php($store = app()->bound('current_store') ? app('current_store') : null)
        @php($announcement = data_get($themeSettings ?? [], 'announcement', []))
        @php($mainLinks = ($mainNavigation ?? []) !== [] ? $mainNavigation : [['label' => 'Collections', 'url' => route('collections.index'), 'external' => false], ['label' => 'Search', 'url' => route('search.index'), 'external' => false]])
        @php($footerLinks = ($footerNavigation ?? []) !== [] ? $footerNavigation : [['label' => 'Collections', 'url' => route('collections.index'), 'external' => false], ['label' => 'Search', 'url' => route('search.index'), 'external' => false]])

        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-zinc-950 focus:shadow dark:focus:bg-zinc-900 dark:focus:text-white">
            Skip to main content
        </a>

        @if (data_get($announcement, 'enabled', false))
            <div class="border-b border-zinc-200 bg-zinc-950 px-4 py-2 text-center text-sm text-white dark:border-zinc-800">
                @if (data_get($announcement, 'url'))
                    <a href="{{ data_get($announcement, 'url') }}" class="underline-offset-4 hover:underline" wire:navigate>
                        {{ data_get($announcement, 'text') }}
                    </a>
                @else
                    {{ data_get($announcement, 'text') }}
                @endif
            </div>
        @endif

        <header @class([
            'top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95',
            'sticky' => data_get($themeSettings ?? [], 'header.sticky', true),
        ])>
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-lg font-semibold tracking-normal" wire:navigate>
                    {{ $store?->name ?? config('app.name') }}
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium md:flex" aria-label="Main navigation">
                    @foreach ($mainLinks as $item)
                        <a href="{{ $item['url'] }}" class="text-zinc-600 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white" @unless ($item['external']) wire:navigate @endunless>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <flux:button :href="route('search.index')" wire:navigate variant="subtle" icon="magnifying-glass" aria-label="Search" />
                    <flux:button :href="route('account.dashboard')" wire:navigate variant="subtle" icon="user" aria-label="Account" />
                    <flux:button href="#" variant="subtle" icon="shopping-bag" aria-label="Cart" />
                </div>
            </div>
        </header>

        <main id="main-content" class="min-h-screen">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm sm:px-6 md:grid-cols-3 lg:px-8">
                <div class="space-y-2">
                    <h2 class="font-semibold text-zinc-950 dark:text-white">{{ $store?->name ?? config('app.name') }}</h2>
                    <p class="max-w-sm text-zinc-600 dark:text-zinc-400">{{ data_get($themeSettings ?? [], 'footer.tagline') }}</p>
                </div>

                <div class="space-y-2">
                    <h2 class="font-semibold text-zinc-950 dark:text-white">Shop</h2>
                    <div class="flex flex-col gap-2 text-zinc-600 dark:text-zinc-400">
                        @foreach ($footerLinks as $item)
                            <a href="{{ $item['url'] }}" @unless ($item['external']) wire:navigate @endunless>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2">
                    <h2 class="font-semibold text-zinc-950 dark:text-white">Customer</h2>
                    <div class="flex flex-col gap-2 text-zinc-600 dark:text-zinc-400">
                        <a href="{{ route('account.login') }}" wire:navigate>Account</a>
                        <a href="{{ route('pages.show', 'about') }}" wire:navigate>About</a>
                    </div>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
