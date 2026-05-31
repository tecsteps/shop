@php
    use App\Services\NavigationService;
    use App\Services\ThemeSettingsService;

    $themeSettings = app(ThemeSettingsService::class);
    $navigation = app(NavigationService::class);

    $store = $currentStore ?? (app()->bound('current_store') ? app('current_store') : null);
    $storeName = $store?->name ?? config('app.name');

    $mainMenu = $navigation->tree('main-menu') ?? [];
    $footerMenu = $navigation->tree('footer-menu') ?? [];

    $announcement = $themeSettings->get('announcement', []);
    $stickyHeader = (bool) $themeSettings->get('header.sticky', true);
    $logoUrl = $themeSettings->get('header.logo_url');
    $darkMode = $themeSettings->get('dark_mode', 'system');
    $social = array_filter($themeSettings->get('footer.social', []));
    $footerDescription = $themeSettings->get('footer.description', '');

    $isLoggedIn = auth('customer')->check();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="scroll-smooth @if ($darkMode === 'dark') dark @endif"
      @if ($darkMode === 'system')
          x-data="{}"
          x-init="document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches)"
      @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $metaDescription ?? $themeSettings->get('home.hero.subheading', '') }}">
    <title>{{ isset($title) ? $title.' - '.$storeName : $storeName }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body class="min-h-screen bg-white font-sans text-zinc-700 antialiased dark:bg-zinc-950 dark:text-zinc-300">
    {{-- Skip link: first focusable element in the body. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-zinc-800">
        {{ __('Skip to main content') }}
    </a>

    {{-- Announcement bar. --}}
    @if (! empty($announcement['enabled']) && ! empty($announcement['text']))
        <div x-data="{ dismissed: $persist(false).as('announcement-dismissed') }"
             x-show="!dismissed"
             x-cloak
             class="relative text-white dark:text-zinc-900"
             style="background-color: {{ $announcement['background_color'] ?? '#171717' }};">
            <div class="mx-auto flex max-w-7xl items-center justify-center px-10 py-2 text-center text-sm">
                <p>
                    {{ $announcement['text'] }}
                    @if (! empty($announcement['link']))
                        <a href="{{ $announcement['link'] }}" class="underline">{{ __('Learn more') }}</a>
                    @endif
                </p>
                <button type="button" x-on:click="dismissed = true"
                        class="absolute right-4 top-1/2 -translate-y-1/2 opacity-80 transition hover:opacity-100"
                        aria-label="{{ __('Dismiss announcement') }}">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>
    @endif

    {{-- Header / primary navigation. --}}
    <header x-data="{ mobileNav: false }"
            class="@if ($stickyHeader) sticky top-0 z-40 bg-white/80 backdrop-blur-md dark:bg-zinc-950/80 @else bg-white dark:bg-zinc-950 @endif border-b border-zinc-200 dark:border-zinc-800"
            role="banner">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            {{-- Mobile hamburger. --}}
            <button type="button" x-on:click="mobileNav = true"
                    class="-ml-1 inline-flex size-10 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 lg:hidden dark:text-zinc-200 dark:hover:bg-zinc-800"
                    aria-label="{{ __('Open menu') }}">
                <flux:icon.bars-3 class="size-6" />
            </button>

            {{-- Logo. --}}
            <a href="{{ route('storefront.home') }}" wire:navigate
               class="flex shrink-0 items-center lg:flex-none">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-8 w-auto lg:h-10" />
                @else
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white">{{ $storeName }}</span>
                @endif
            </a>

            {{-- Desktop navigation with one level of dropdowns. --}}
            <nav class="hidden flex-1 items-center justify-center gap-6 lg:flex" aria-label="{{ __('Main') }}">
                @foreach ($mainMenu as $item)
                    @if (empty($item['children']))
                        <a href="{{ $item['url'] }}" wire:navigate
                           class="text-sm font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <div class="relative" x-data="{ open: false }"
                             x-on:mouseenter="open = true" x-on:mouseleave="open = false">
                            <button type="button" x-on:click="open = !open" x-on:focus="open = true"
                                    class="flex items-center gap-1 text-sm font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                    x-bind:aria-expanded="open" aria-haspopup="true">
                                {{ $item['label'] }}
                                <flux:icon.chevron-down class="size-4" />
                            </button>
                            <div x-show="open" x-cloak x-transition
                                 x-on:keydown.escape.window="open = false"
                                 class="absolute left-0 top-full z-50 mt-2 min-w-48 rounded-xl border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                                @foreach ($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" wire:navigate
                                       class="block rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            {{-- Action icons. --}}
            <div class="flex items-center gap-1">
                <button type="button" x-on:click="$dispatch('open-search-modal')"
                        class="inline-flex size-10 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        aria-label="{{ __('Search') }}">
                    <flux:icon.magnifying-glass class="size-5" />
                </button>

                <a href="{{ $isLoggedIn ? route('account.dashboard') : route('account.login') }}" wire:navigate
                   class="hidden size-10 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 sm:inline-flex dark:text-zinc-200 dark:hover:bg-zinc-800"
                   aria-label="{{ $isLoggedIn ? __('Your account') : __('Log in') }}">
                    <flux:icon.user class="size-5" />
                </a>

                <button type="button" x-on:click="$dispatch('open-cart-drawer')"
                        class="relative inline-flex size-10 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        aria-label="{{ __('Cart') }}">
                    <flux:icon.shopping-bag class="size-5" />
                    {{-- Cart count badge: updated by the CartDrawer component (task #6). --}}
                    <span x-data="{ count: 0 }"
                          x-on:cart-updated.window="count = $event.detail?.itemCount ?? count"
                          x-show="count > 0" x-cloak
                          x-text="count"
                          class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-blue-600 px-1.5 text-xs font-semibold text-white"></span>
                </button>
            </div>
        </div>

        {{-- Mobile navigation drawer. --}}
        <div x-show="mobileNav" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('Mobile navigation') }}">
            <div x-show="mobileNav" x-transition.opacity x-on:click="mobileNav = false"
                 class="fixed inset-0 bg-zinc-900/50"></div>
            <div x-show="mobileNav"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 x-on:keydown.escape.window="mobileNav = false"
                 class="fixed inset-y-0 left-0 flex w-80 max-w-[80%] flex-col bg-white shadow-xl dark:bg-zinc-950">
                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ __('Menu') }}</span>
                    <button type="button" x-on:click="mobileNav = false"
                            class="inline-flex size-10 items-center justify-center rounded-lg text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            aria-label="{{ __('Close menu') }}">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto p-4" aria-label="{{ __('Mobile') }}">
                    <ul class="space-y-1">
                        @foreach ($mainMenu as $item)
                            <li>
                                @if (empty($item['children']))
                                    <a href="{{ $item['url'] }}" wire:navigate
                                       class="block rounded-lg px-3 py-3 text-base font-medium text-zinc-800 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800">
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <div x-data="{ open: false }">
                                        <button type="button" x-on:click="open = !open"
                                                class="flex w-full items-center justify-between rounded-lg px-3 py-3 text-base font-medium text-zinc-800 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800"
                                                x-bind:aria-expanded="open">
                                            {{ $item['label'] }}
                                            <flux:icon.chevron-down class="size-5 transition" x-bind:class="open && 'rotate-180'" />
                                        </button>
                                        <ul x-show="open" x-collapse class="ml-3 space-y-1 border-l border-zinc-200 pl-2 dark:border-zinc-800">
                                            @foreach ($item['children'] as $child)
                                                <li>
                                                    <a href="{{ $child['url'] }}" wire:navigate
                                                       class="block rounded-lg px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                                        {{ $child['label'] }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                    <a href="{{ $isLoggedIn ? route('account.dashboard') : route('account.login') }}" wire:navigate
                       class="flex items-center gap-2 rounded-lg px-3 py-3 text-base font-medium text-zinc-800 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800">
                        <flux:icon.user class="size-5" />
                        {{ $isLoggedIn ? __('Your account') : __('Log in') }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Main content. --}}
    <main id="main-content" class="min-h-[60vh]">
        {{ $slot }}
    </main>

    {{-- Footer. --}}
    <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900" role="contentinfo">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($footerMenu as $column)
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">{{ $column['label'] }}</h2>
                        @if (! empty($column['children']))
                            <ul class="mt-4 space-y-2">
                                @foreach ($column['children'] as $link)
                                    <li>
                                        <a href="{{ $link['url'] }}" wire:navigate
                                           class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                            {{ $link['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach

                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">{{ $storeName }}</h2>
                    @if ($footerDescription)
                        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $footerDescription }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-zinc-200 pt-8 sm:flex-row dark:border-zinc-800">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    &copy; {{ now()->year }} {{ $storeName }}. {{ __('All rights reserved.') }}
                </p>

                @if (! empty($social))
                    <div class="flex items-center gap-4">
                        @foreach ($social as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               class="text-zinc-400 transition hover:text-zinc-900 dark:hover:text-white">
                                <span class="sr-only">{{ ucfirst($network) }}</span>
                                <flux:icon.link variant="micro" class="size-5" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </footer>

    {{-- Global cart drawer + search modal slot. The CartDrawer and SearchModal
         Livewire components are built in the shopping UI phase (task #6) and
         dropped in here so the header's open-cart-drawer / open-search-modal
         events have a listener. Until then the slot is empty. --}}
    {{ $globals ?? '' }}

    @livewireScripts
    @fluxScripts
</body>
</html>
