@php
    use App\Models\NavigationMenu;
    use App\Services\NavigationService;
    use App\Services\ThemeSettingsService;

    $store = app('current_store');
    $settings = app(ThemeSettingsService::class)->forStore($store);
    $navigation = app(NavigationService::class);
    $mainMenu = NavigationMenu::query()->where('handle', 'main-menu')->first();
    $footerMenu = NavigationMenu::query()->where('handle', 'footer-menu')->first();
    $mainItems = $mainMenu ? $navigation->buildTree($mainMenu) : [];
    $footerItems = $footerMenu ? $navigation->buildTree($footerMenu) : [];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>
            document.documentElement.classList.toggle(
                'dark',
                localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
            );
        </script>
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
        <a href="#main-content" class="sr-only fixed left-4 top-4 z-50 rounded-md bg-white px-4 py-2 text-sm font-semibold text-zinc-950 shadow focus:not-sr-only dark:bg-zinc-900 dark:text-white">
            Skip to main content
        </a>

        @if(data_get($settings, 'announcement.enabled'))
            <div class="bg-zinc-950 px-4 py-2 text-center text-sm text-white dark:bg-white dark:text-zinc-950">
                @if(data_get($settings, 'announcement.link'))
                    <a href="{{ data_get($settings, 'announcement.link') }}" class="underline underline-offset-4">
                        {{ data_get($settings, 'announcement.text') }}
                    </a>
                @else
                    {{ data_get($settings, 'announcement.text') }}
                @endif
            </div>
        @endif

        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <details class="lg:hidden">
                    <summary class="cursor-pointer list-none rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium dark:border-zinc-700">
                        Menu
                    </summary>
                    <nav aria-label="Mobile navigation" class="absolute left-0 right-0 top-full border-b border-zinc-200 bg-white px-4 py-4 shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="grid gap-3">
                            @foreach($mainItems as $item)
                                <a wire:key="mobile-nav-{{ $item['label'] }}" href="{{ $item['url'] }}" class="rounded-md px-3 py-2 text-base font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </nav>
                </details>

                <a href="/" class="text-lg font-semibold tracking-normal">
                    {{ $store->name }}
                </a>

                <nav aria-label="Main navigation" class="hidden items-center gap-6 lg:flex">
                    @foreach($mainItems as $item)
                        <a wire:key="desktop-nav-{{ $item['label'] }}" href="{{ $item['url'] }}" class="text-sm font-medium text-zinc-700 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <a href="/search" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">Search</a>
                    <a href="/account" class="rounded-md px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900">Account</a>
                    <a href="/cart" class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Cart</a>
                </div>
            </div>
        </header>

        <main id="main-content" class="min-h-screen">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
                <div>
                    <div class="text-base font-semibold">{{ $store->name }}</div>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                        {{ data_get($settings, 'footer.contact_email') }}
                    </p>
                </div>
                <nav aria-label="Footer navigation" class="grid gap-2 text-sm md:col-span-2 md:grid-cols-2">
                    @foreach($footerItems as $item)
                        <a wire:key="footer-nav-{{ $item['label'] }}" href="{{ $item['url'] }}" class="text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
            <div class="mx-auto max-w-7xl px-4 pb-8 text-sm text-zinc-500 sm:px-6 lg:px-8">
                &copy; {{ now()->year }} {{ $store->name }}. All rights reserved.
            </div>
        </footer>

        @livewire('storefront.cart-drawer')
        @fluxScripts
    </body>
</html>
