<header
    x-data="{ mobileNavOpen: false }"
    x-init="$watch('mobileNavOpen', (value) => { if (value) $nextTick(() => $refs.mobileNavClose?.focus()) })"
    x-on:keydown.escape.window="mobileNavOpen = false"
    class="{{ $themeSettings['sticky_header'] ? 'sticky top-0 z-40 border-b border-zinc-200/80 bg-white/90 backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-950/90' : 'border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950' }}"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        {{-- Mobile: hamburger --}}
        <button
            type="button"
            x-on:click="mobileNavOpen = true"
            class="-ml-2 rounded-lg p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-800"
            aria-label="{{ __('Open navigation menu') }}"
            x-bind:aria-expanded="mobileNavOpen"
        >
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        {{-- Logo / store name --}}
        <a
            href="{{ route('home') }}"
            class="absolute left-1/2 -translate-x-1/2 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 lg:static lg:translate-x-0"
        >
            @if (filled($themeSettings['logo_url']))
                <img src="{{ $themeSettings['logo_url'] }}" alt="{{ $storeName }}" class="max-h-8 w-auto lg:max-h-10" />
            @else
                <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white">{{ $storeName }}</span>
            @endif
        </a>

        {{-- Desktop navigation --}}
        <nav class="hidden flex-1 items-center justify-center lg:flex" aria-label="{{ __('Main navigation') }}">
            <ul class="flex items-center gap-1">
                @foreach ($mainMenu as $item)
                    <li>
                        <a
                            href="{{ $item['url'] }}"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                        >
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Action icons --}}
        <div class="flex items-center gap-1">
            {{-- Search: opens the search modal --}}
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-search-modal')"
                class="rounded-lg p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                aria-label="{{ __('Search') }}"
                aria-haspopup="dialog"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </button>

            {{-- Account --}}
            <a
                href="{{ auth('customer')->check() ? route('storefront.account.index') : route('storefront.account.login') }}"
                class="hidden rounded-lg p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 lg:block dark:text-zinc-300 dark:hover:bg-zinc-800"
                aria-label="{{ auth('customer')->check() ? __('My account') : __('Log in') }}"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </a>

            {{-- Cart: opens the cart drawer; the badge updates from "cart-updated" browser events --}}
            <button
                type="button"
                x-data="{ count: {{ (int) ($cartItemCount ?? 0) }} }"
                x-on:cart-updated.window="count = $event.detail.itemCount ?? count"
                x-on:click="$dispatch('open-cart')"
                class="relative rounded-lg p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                aria-label="{{ __('Open cart') }}"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                </svg>
                <span
                    x-show="count > 0"
                    x-text="count > 99 ? '99+' : count"
                    x-cloak
                    class="absolute -top-0.5 -right-0.5 flex min-w-5 items-center justify-center rounded-full px-1 py-0.5 text-[10px] leading-none font-semibold text-white"
                    style="background-color: var(--sf-primary, #2563eb);"
                    aria-hidden="true"
                ></span>
                <span class="sr-only" aria-live="polite" x-text="count + ' {{ __('items in cart') }}'"></span>
            </button>
        </div>
    </div>

    {{-- Mobile navigation drawer --}}
    <div x-show="mobileNavOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('Mobile navigation') }}">
        <div
            x-show="mobileNavOpen"
            x-transition.opacity.duration.200ms
            x-on:click="mobileNavOpen = false"
            class="fixed inset-0 bg-zinc-950/50"
            aria-hidden="true"
        ></div>
        <div
            x-show="mobileNavOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 flex w-full max-w-xs flex-col bg-white shadow-xl dark:bg-zinc-900"
        >
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
                <span class="text-base font-bold text-zinc-900 dark:text-white">{{ $storeName }}</span>
                <button
                    type="button"
                    x-on:click="mobileNavOpen = false"
                    x-ref="mobileNavClose"
                    class="rounded-lg p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    aria-label="{{ __('Close navigation menu') }}"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-2 py-4" aria-label="{{ __('Mobile navigation') }}">
                <ul class="space-y-1">
                    @foreach ($mainMenu as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                class="block rounded-lg px-3 py-3 text-base font-medium text-zinc-800 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            <div class="border-t border-zinc-200 px-2 py-4 dark:border-zinc-800">
                <a
                    href="{{ auth('customer')->check() ? route('storefront.account.index') : route('storefront.account.login') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-3 text-base font-medium text-zinc-800 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    {{ auth('customer')->check() ? __('My account') : __('Log in') }}
                </a>
            </div>
        </div>
    </div>
</header>
