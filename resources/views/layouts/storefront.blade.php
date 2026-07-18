@php
    use App\Services\NavigationService;

    $store = app('current_store');
    $navigation = app(NavigationService::class);
    $mainMenu = $navigation->menu($store, 'main-menu');
    $footerMenu = $navigation->menu($store, 'footer-menu');
    $publishedTheme = $store->themes()->where('status', \App\Enums\ThemeStatus::Published)->first();
    $themeSettings = $publishedTheme?->settings?->settings_json ?? [];
    $announcement = $themeSettings['header']['announcement_text'] ?? null;
    $showAnnouncement = (bool) ($themeSettings['header']['show_announcement_bar'] ?? false) && $announcement;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ $title ?? $store->name }}</title>
        <meta name="description" content="{{ $metaDescription ?? $store->name }}" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @fluxAppearance
    </head>
    <body class="bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <a
            href="#main-content"
            class="sr-only rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:bg-white dark:text-zinc-900"
        >
            Skip to main content
        </a>

        @if ($showAnnouncement)
            <div
                x-data="{ dismissed: localStorage.getItem('announcement-dismissed') === '1' }"
                x-show="!dismissed"
                class="relative bg-zinc-900 px-4 py-2 text-center text-sm text-white dark:bg-white dark:text-zinc-900"
            >
                <span>{{ $announcement }}</span>
                @if (! empty($themeSettings['header']['announcement_link']))
                    <a href="{{ $themeSettings['header']['announcement_link'] }}" class="ml-2 underline">Learn more</a>
                @endif
                <button
                    type="button"
                    @click="dismissed = true; localStorage.setItem('announcement-dismissed', '1')"
                    class="absolute inset-y-0 right-3 flex items-center"
                    aria-label="Dismiss announcement"
                >
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
        @endif

        <header
            x-data="{ mobileNavOpen: false }"
            class="sticky top-0 z-30 border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90"
        >
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <button
                    type="button"
                    @click="mobileNavOpen = true"
                    class="-ml-2 flex size-10 items-center justify-center rounded-lg text-zinc-700 hover:bg-zinc-100 lg:hidden dark:text-zinc-200 dark:hover:bg-zinc-800"
                    aria-label="Open mobile navigation"
                >
                    <flux:icon name="bars-3" class="size-6" />
                </button>

                <a href="{{ route('home') }}" wire:navigate class="text-lg font-bold tracking-tight lg:order-first">
                    {{ $store->name }}
                </a>

                <nav aria-label="Main" class="hidden items-center gap-6 lg:flex">
                    @foreach ($mainMenu as $item)
                        <a href="{{ $item['url'] }}" wire:navigate class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-1">
                    <a
                        href="{{ route('storefront.search.index') }}"
                        wire:navigate
                        class="flex size-10 items-center justify-center rounded-lg text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        aria-label="Search"
                    >
                        <flux:icon name="magnifying-glass" class="size-5" />
                    </a>

                    <livewire:storefront.cart.cart-drawer />

                    <a
                        href="{{ Auth::guard('customer')->check() ? route('storefront.account.dashboard') : route('storefront.account.login') }}"
                        wire:navigate
                        class="flex size-10 items-center justify-center rounded-lg text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        aria-label="Account"
                    >
                        <flux:icon name="user" class="size-5" />
                    </a>
                </div>
            </div>

            <!-- Mobile navigation drawer -->
            <div x-show="mobileNavOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Mobile navigation">
                <div
                    x-show="mobileNavOpen"
                    x-transition.opacity
                    @click="mobileNavOpen = false"
                    class="fixed inset-0 bg-black/50"
                ></div>

                <div
                    x-show="mobileNavOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    @keydown.escape.window="mobileNavOpen = false"
                    class="fixed inset-y-0 left-0 flex w-full max-w-xs flex-col bg-white p-6 shadow-xl dark:bg-zinc-900"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold">{{ $store->name }}</span>
                        <button type="button" @click="mobileNavOpen = false" aria-label="Close mobile navigation" class="flex size-10 items-center justify-center rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>

                    <nav class="mt-6 flex flex-col gap-1">
                        @foreach ($mainMenu as $item)
                            <a href="{{ $item['url'] }}" wire:navigate class="rounded-lg px-3 py-3 text-base font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <a
                        href="{{ Auth::guard('customer')->check() ? route('storefront.account.dashboard') : route('storefront.account.login') }}"
                        wire:navigate
                        class="mt-auto rounded-lg px-3 py-3 text-base font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        {{ Auth::guard('customer')->check() ? 'My Account' : 'Log in' }}
                    </a>
                </div>
            </div>
        </header>

        <main id="main-content" class="min-h-[60vh]">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900" role="contentinfo">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Shop</h3>
                        <ul class="mt-4 space-y-2">
                            @forelse ($footerMenu as $item)
                                <li><a href="{{ $item['url'] }}" wire:navigate class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ $item['label'] }}</a></li>
                            @empty
                                <li><a href="{{ route('storefront.collections.index') }}" wire:navigate class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">All Collections</a></li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-span-2 sm:col-span-1 lg:col-start-4">
                        <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $store->name }}</h3>
                        <address class="mt-4 space-y-1 text-sm text-zinc-600 not-italic dark:text-zinc-400">
                            <p>{{ $store->name }}</p>
                            @isset($store->settings->settings_json['support_email'])
                                <p>{{ $store->settings->settings_json['support_email'] }}</p>
                            @endisset
                        </address>

                        <div class="mt-4 flex gap-3">
                            @foreach (['Facebook', 'Instagram', 'Twitter/X', 'TikTok', 'YouTube'] as $network)
                                <a href="#" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200" aria-label="{{ $store->name }} on {{ $network }}">
                                    <flux:icon name="link" class="size-5" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-zinc-200 pt-6 sm:flex-row dark:border-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        &copy; {{ now()->year }} {{ $store->name }}. All rights reserved.
                    </p>
                    <div class="flex items-center gap-3 opacity-70">
                        @foreach (['Visa', 'Mastercard', 'Amex', 'PayPal'] as $method)
                            <span class="rounded border border-zinc-300 px-2 py-1 text-[10px] font-semibold text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">{{ $method }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
