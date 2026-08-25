@php
    $sticky = $settings['sticky_header'] ?? true;
    $logoUrl = $settings['logo_url'] ?? null;
@endphp

<header
    x-data="{ mobileOpen: false, scrolled: false }"
    @scroll.window="scrolled = window.scrollY > 8"
    :class="scrolled ? 'border-b border-zinc-200 shadow-sm dark:border-zinc-800' : 'border-b border-transparent'"
    class="{{ $sticky ? 'sticky top-0 z-40' : '' }} border-b bg-white/90 backdrop-blur transition dark:bg-zinc-950/90"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4 lg:h-20">
            {{-- Mobile hamburger --}}
            <button
                type="button"
                @click="mobileOpen = true"
                aria-label="Open navigation"
                aria-expanded="false"
                class="-ml-2 inline-flex h-11 w-11 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 lg:hidden dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            {{-- Logo --}}
            <a href="{{ route('storefront.home') }}" class="flex items-center" aria-label="{{ $storeName }} home">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-8 max-h-8 w-auto lg:h-10 lg:max-h-10" />
                @else
                    <span class="text-lg font-bold tracking-tight text-zinc-900 lg:text-xl dark:text-white">{{ $storeName }}</span>
                @endif
            </a>

            {{-- Desktop navigation --}}
            <nav aria-label="Main navigation" class="hidden flex-1 items-center justify-center lg:flex">
                <ul class="flex items-center gap-1">
                    @foreach ($mainNav as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white"
                            >
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-0.5 sm:gap-1">
                <button
                    type="button"
                    @click="openSearch()"
                    aria-label="Search products"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </button>

                @if ($customer)
                    <a
                        href="{{ route('account.dashboard') }}"
                        aria-label="Your account"
                        class="hidden h-11 w-11 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 sm:inline-flex dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </a>
                @else
                    <a
                        href="{{ route('account.login') }}"
                        aria-label="Log in"
                        class="hidden h-11 w-11 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 sm:inline-flex dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </a>
                @endif

                <button
                    type="button"
                    @click="openCart()"
                    aria-label="Open shopping cart"
                    class="relative inline-flex h-11 w-11 items-center justify-center rounded-lg text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="8" cy="21" r="1" />
                        <circle cx="19" cy="21" r="1" />
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                    </svg>
                    <span
                        x-show="cartCount > 0"
                        x-cloak
                        class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-600 px-1 text-[11px] font-semibold text-white dark:bg-blue-500"
                    >
                        <span x-text="cartCount">0</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    @include('storefront.partials.mobile-nav', [
        'storeName' => $storeName,
        'mainNav' => $mainNav,
        'customer' => $customer,
    ])
</header>
