@php
    $themeSettings = app(\App\Services\ThemeSettingsService::class);
    $store = app()->bound('current_store') ? app('current_store') : null;
    $storeName = $store?->name ?? config('app.name');

    $showAnnouncement = $themeSettings->get('show_announcement_bar', false);
    $announcementText = $themeSettings->get('announcement_text', '');

    $mainMenu = null;
    $footerMenu = null;
    if ($store) {
        $mainMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'main-menu')
            ->with(['items' => fn ($q) => $q->whereNull('parent_id')->orderBy('position')->with('children')])
            ->first();
        $footerMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'footer-menu')
            ->with(['items' => fn ($q) => $q->whereNull('parent_id')->orderBy('position')->with('children')])
            ->first();
    }

    $resolveNavUrl = function (\App\Models\NavigationItem $item): string {
        return match ($item->type) {
            \App\Enums\NavigationItemType::Collection => route('storefront.collections.show', ['handle' => \App\Models\Collection::withoutGlobalScopes()->find($item->resource_id)?->handle ?? 'unknown']),
            \App\Enums\NavigationItemType::Product => route('storefront.products.show', ['handle' => \App\Models\Product::withoutGlobalScopes()->find($item->resource_id)?->handle ?? 'unknown']),
            \App\Enums\NavigationItemType::Page => route('storefront.pages.show', ['handle' => \App\Models\Page::withoutGlobalScopes()->find($item->resource_id)?->handle ?? 'unknown']),
            default => $item->url ?? '#',
        };
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (isset($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
    @endif

    <title>{{ isset($title) ? $title . ' - ' . $storeName : $storeName }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 antialiased">
    {{-- Skip link --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:bg-white focus:dark:bg-zinc-800 focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:ring-2 focus:ring-blue-500 focus:text-zinc-900 focus:dark:text-white">
        Skip to main content
    </a>

    {{-- Announcement bar --}}
    @if ($showAnnouncement && $announcementText)
        <div
            x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
            x-show="!dismissed"
            x-cloak
            class="bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 text-center text-sm py-2 px-4 relative"
        >
            <p>{{ $announcementText }}</p>
            <button
                @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-white dark:text-zinc-900 hover:opacity-70"
                aria-label="Dismiss announcement"
            >
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif

    {{-- Header --}}
    <header class="border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Mobile hamburger --}}
                <button
                    x-data
                    @click="$dispatch('open-mobile-nav')"
                    class="lg:hidden flex items-center justify-center p-2 -ml-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                    aria-label="Open navigation menu"
                >
                    <flux:icon name="bars-3" class="size-6" />
                </button>

                {{-- Logo --}}
                <a href="{{ route('storefront.home') }}" class="flex items-center shrink-0" wire:navigate>
                    <span class="text-xl font-bold text-zinc-900 dark:text-white">{{ $storeName }}</span>
                </a>

                {{-- Desktop navigation --}}
                @if ($mainMenu)
                    <nav class="hidden lg:flex items-center gap-6" aria-label="Main navigation">
                        @foreach ($mainMenu->items as $item)
                            <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <a
                                    href="{{ $resolveNavUrl($item) }}"
                                    class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors py-2 inline-flex items-center gap-1"
                                    wire:navigate
                                    @if ($item->children->count())
                                        @keydown.enter="open = !open"
                                        aria-haspopup="true"
                                        :aria-expanded="open"
                                    @endif
                                >
                                    {{ $item->title }}
                                    @if ($item->children->count())
                                        <flux:icon name="chevron-down" class="size-3" />
                                    @endif
                                </a>
                                @if ($item->children->count())
                                    <div
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute top-full left-0 mt-1 w-48 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50"
                                    >
                                        @foreach ($item->children->sortBy('position') as $child)
                                            <a
                                                href="{{ $resolveNavUrl($child) }}"
                                                class="block px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-white"
                                                wire:navigate
                                            >
                                                {{ $child->title }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </nav>
                @endif

                {{-- Right group --}}
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('storefront.search') }}"
                        class="p-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors"
                        aria-label="Search"
                        wire:navigate
                    >
                        <flux:icon name="magnifying-glass" class="size-5" />
                    </a>

                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-cart-drawer')"
                        class="p-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors relative"
                        aria-label="Cart"
                    >
                        <flux:icon name="shopping-bag" class="size-5" />
                        <livewire:storefront.cart-count />
                    </button>

                    @auth('customer')
                        <a
                            href="{{ route('customer.account') }}"
                            class="p-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors"
                            aria-label="My account"
                            wire:navigate
                        >
                            <flux:icon name="user" class="size-5" />
                        </a>
                    @else
                        <a
                            href="{{ route('customer.login') }}"
                            class="p-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors"
                            aria-label="Sign in"
                            wire:navigate
                        >
                            <flux:icon name="user" class="size-5" />
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Mobile navigation drawer --}}
    <div
        x-data="{ open: false }"
        @open-mobile-nav.window="open = true"
        x-cloak
    >
        <div
            x-show="open"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-40"
            @click="open = false"
            aria-hidden="true"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 w-80 max-w-full bg-white dark:bg-zinc-900 z-50 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-label="Mobile navigation"
            @keydown.escape.window="open = false"
        >
            <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $storeName }}</span>
                <button @click="open = false" class="p-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" aria-label="Close navigation">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </div>

            @if ($mainMenu)
                <nav class="p-4 space-y-1" aria-label="Mobile navigation">
                    @foreach ($mainMenu->items as $item)
                        @if ($item->children->count())
                            <div x-data="{ expanded: false }">
                                <button
                                    @click="expanded = !expanded"
                                    class="flex items-center justify-between w-full py-3 px-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg"
                                    :aria-expanded="expanded"
                                >
                                    {{ $item->title }}
                                    <flux:icon name="chevron-down" class="size-4 transition-transform" ::class="expanded ? 'rotate-180' : ''" />
                                </button>
                                <div x-show="expanded" x-collapse class="pl-4">
                                    <a href="{{ $resolveNavUrl($item) }}" class="block py-2 px-2 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white rounded-lg" wire:navigate @click="open = false">
                                        All {{ $item->title }}
                                    </a>
                                    @foreach ($item->children->sortBy('position') as $child)
                                        <a href="{{ $resolveNavUrl($child) }}" class="block py-2 px-2 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white rounded-lg" wire:navigate @click="open = false">
                                            {{ $child->title }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $resolveNavUrl($item) }}" class="block py-3 px-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg" wire:navigate @click="open = false">
                                {{ $item->title }}
                            </a>
                        @endif
                    @endforeach

                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 mt-4">
                        @auth('customer')
                            <a href="{{ route('customer.account') }}" class="block py-3 px-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg" wire:navigate @click="open = false">
                                My Account
                            </a>
                        @else
                            <a href="{{ route('customer.login') }}" class="block py-3 px-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg" wire:navigate @click="open = false">
                                Sign In
                            </a>
                        @endauth
                    </div>
                </nav>
            @endif
        </div>
    </div>

    {{-- Cart drawer --}}
    <livewire:storefront.cart-drawer />

    {{-- Main content --}}
    <main id="main-content" class="min-h-screen">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-zinc-50 dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if ($footerMenu && $footerMenu->items->count())
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8 mb-8">
                    @foreach ($footerMenu->items as $item)
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3">
                                {{ $item->title }}
                            </h3>
                            @if ($item->children->count())
                                <ul class="space-y-2">
                                    @foreach ($item->children->sortBy('position') as $child)
                                        <li>
                                            <a
                                                href="{{ $resolveNavUrl($child) }}"
                                                class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors"
                                                wire:navigate
                                            >
                                                {{ $child->title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="border-t border-zinc-200 dark:border-zinc-800 pt-8">
                <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center">
                    &copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
