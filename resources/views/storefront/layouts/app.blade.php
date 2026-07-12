@php
    $mode = data_get($themeSettings, 'dark_mode', 'system');
    $mode = in_array($mode, ['system', 'toggle', 'light', 'dark'], true) ? $mode : 'system';
    $primary = data_get($themeSettings, 'colors.primary', '#2563eb');
    $secondary = data_get($themeSettings, 'colors.secondary', '#18181b');
    $accent = data_get($themeSettings, 'colors.accent', '#ea580c');
    $primary = preg_match('/^#[0-9a-f]{6}$/i', (string) $primary) ? $primary : '#2563eb';
    $secondary = preg_match('/^#[0-9a-f]{6}$/i', (string) $secondary) ? $secondary : '#18181b';
    $accent = preg_match('/^#[0-9a-f]{6}$/i', (string) $accent) ? $accent : '#ea580c';

    $cartCount = 0;
    $cartId = session('cart_id');
    if ($cartId && class_exists(\App\Models\Cart::class)) {
        $cartCount = (int) \App\Models\CartLine::query()->where('cart_id', $cartId)->sum('quantity');
    } elseif ($storefrontCustomer && class_exists(\App\Models\Cart::class)) {
        $cart = \App\Models\Cart::withoutGlobalScopes()->where('store_id', $currentStore->id)->where('customer_id', $storefrontCustomer->getAuthIdentifier())->where('status', 'active')->latest('id')->first();
        $cartCount = $cart ? (int) $cart->lines()->sum('quantity') : 0;
    }

    app()->setLocale((string) ($currentStore->default_locale ?: config('app.locale')));

    $organizationJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $currentStore->name,
        'url' => url('/'),
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<x-storefront.layout :$title :$metaDescription :dark-mode="$mode" style="--storefront-primary: {{ $primary }}; --storefront-primary-hover: {{ $primary }}; --storefront-primary-soft: color-mix(in srgb, {{ $primary }} 10%, transparent); --storefront-secondary: {{ $secondary }}; --storefront-accent: {{ $accent }};">
    @if (data_get($themeSettings, 'announcement.enabled') && data_get($themeSettings, 'announcement.text'))
        <x-slot:announcement>
            <div x-data="{ visible: false }" x-init="visible = localStorage.getItem('announcement-{{ $currentStore->id }}') !== 'dismissed'" x-show="visible" x-cloak class="relative px-12 py-2.5 text-center text-sm font-medium text-white" style="background-color: {{ preg_match('/^#[0-9a-f]{6}$/i', (string) data_get($themeSettings, 'announcement.background')) ? data_get($themeSettings, 'announcement.background') : '#18181b' }}">
                @if(data_get($themeSettings, 'announcement.url'))<a href="{{ data_get($themeSettings, 'announcement.url') }}" class="underline underline-offset-4">{{ data_get($themeSettings, 'announcement.text') }}</a>@else{{ data_get($themeSettings, 'announcement.text') }}@endif
                <button type="button" class="absolute right-2 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-white" @click="visible=false; localStorage.setItem('announcement-{{ $currentStore->id }}', 'dismissed')" aria-label="Dismiss announcement">&times;</button>
            </div>
        </x-slot:announcement>
    @endif

    <x-slot:header>
        <header x-data="{ mobileOpen: false, cartCount: {{ $cartCount }} }" @cart-updated.window="cartCount = Number($event.detail.itemCount ?? 0)" @keydown.escape.window="mobileOpen = false" class="{{ data_get($themeSettings, 'header.sticky', true) ? 'sticky top-0' : 'relative' }} z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
            <nav class="sf-container flex h-16 items-center justify-between gap-4" aria-label="Primary navigation">
                <button type="button" class="sf-icon-button lg:hidden" @click="mobileOpen = true" :aria-expanded="mobileOpen" aria-controls="mobile-navigation" aria-label="Open navigation"><svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.7" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg></button>

                <a href="{{ url('/') }}" wire:navigate class="min-w-0 shrink-0 text-lg font-semibold tracking-tight text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600 dark:text-white">
                    @if(data_get($themeSettings, 'header.logo_url'))<img src="{{ data_get($themeSettings, 'header.logo_url') }}" alt="{{ $currentStore->name }}" class="h-8 w-auto max-w-44 object-contain lg:h-10">@else<span class="truncate">{{ $currentStore->name }}</span>@endif
                </a>

                <ul class="hidden items-center justify-center gap-1 lg:flex">
                    @forelse($mainNavigation as $item)
                        <li><a href="{{ $item['url'] }}" wire:navigate class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white">{{ $item['label'] }}</a></li>
                    @empty
                        <li><a href="{{ url('/collections') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-medium">Collections</a></li>
                        <li><a href="{{ url('/search') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-medium">Search</a></li>
                    @endforelse
                </ul>

                <div class="flex items-center gap-1">
                    <button type="button" class="sf-icon-button hidden sm:grid" x-on:click="Livewire.dispatch('open-search-modal')" aria-label="Search"><svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7" stroke-width="1.7"/><path d="m20 20-4-4" stroke-width="1.7"/></svg></button>
                    <a href="{{ $storefrontCustomer ? url('/account') : url('/account/login') }}" wire:navigate class="sf-icon-button hidden sm:grid" aria-label="{{ $storefrontCustomer ? 'Your account' : 'Log in' }}"><svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4" stroke-width="1.7"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0" stroke-width="1.7"/></svg></a>
                    @if($mode === 'toggle')<button type="button" class="sf-icon-button hidden sm:grid" x-data="{ dark: document.documentElement.classList.contains('dark') }" @click="dark=!dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('storefront-theme', dark ? 'dark' : 'light')" aria-label="Toggle dark mode"><svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.7" d="M20.5 15.5A9 9 0 0 1 8.5 3.5a9 9 0 1 0 12 12Z"/></svg></button>@endif
                    <button type="button" class="sf-icon-button relative" x-on:click="Livewire.dispatch('open-cart-drawer')" aria-label="Open cart"><svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.7" d="M6 8h12l1 13H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2"/></svg><span x-show="cartCount > 0" x-cloak x-text="cartCount" class="absolute -right-1 -top-1 grid min-h-5 min-w-5 place-items-center rounded-full bg-blue-700 px-1 text-[10px] font-bold text-white" aria-label="Items in cart"></span></button>
                </div>
            </nav>

            <div id="mobile-navigation" x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Mobile navigation">
                <button type="button" class="absolute inset-0 bg-slate-950/55" @click="mobileOpen=false" aria-label="Close navigation"></button>
                <div class="absolute inset-y-0 left-0 flex w-full max-w-sm flex-col bg-white shadow-2xl dark:bg-slate-950" x-transition:enter="transition duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" @keydown.tab="const items=[...$el.querySelectorAll('button:not([disabled]),a[href]')]; if(items.length){if($event.shiftKey && document.activeElement===items[0]){$event.preventDefault();items[items.length-1].focus()}else if(!$event.shiftKey && document.activeElement===items[items.length-1]){$event.preventDefault();items[0].focus()}}">
                    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5 dark:border-slate-800"><span class="font-semibold">{{ $currentStore->name }}</span><button type="button" class="sf-icon-button" @click="mobileOpen=false" aria-label="Close navigation">&times;</button></div>
                    <nav class="flex-1 overflow-y-auto p-4" aria-label="Mobile navigation links"><ul class="space-y-1">@forelse($mainNavigation as $item)<li><a href="{{ $item['url'] }}" wire:navigate @click="mobileOpen=false" class="flex min-h-12 items-center rounded-xl px-4 font-medium hover:bg-slate-100 dark:hover:bg-slate-900">{{ $item['label'] }}</a></li>@empty<li><a href="{{ url('/collections') }}" wire:navigate @click="mobileOpen=false" class="flex min-h-12 items-center rounded-xl px-4 font-medium">Collections</a></li>@endforelse<li><button type="button" @click="mobileOpen=false; Livewire.dispatch('open-search-modal')" class="flex min-h-12 w-full items-center rounded-xl px-4 text-left font-medium">Search</button></li></ul></nav>
                    <a href="{{ $storefrontCustomer ? url('/account') : url('/account/login') }}" wire:navigate @click="mobileOpen=false" class="m-4 flex min-h-12 items-center rounded-xl bg-slate-100 px-4 font-medium dark:bg-slate-900">{{ $storefrontCustomer ? 'Your account' : 'Log in' }}</a>
                </div>
            </div>
        </header>
    </x-slot:header>

    {{ $slot }}

    <x-slot:footer>
        <footer class="mt-16 border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950" aria-label="Store footer">
            <div class="sf-container py-12 sm:py-16">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-3 lg:grid-cols-4">
                    <div class="col-span-2 md:col-span-1"><h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ $currentStore->name }}</h2><p class="mt-3 max-w-xs text-sm leading-6 text-slate-500">{{ data_get($themeSettings, 'footer.description', 'Quality products and thoughtful service, all in one place.') }}</p></div>
                    @if($footerNavigation !== [])<div><h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Explore</h2><ul class="mt-4 space-y-2">@foreach($footerNavigation as $item)<li><a href="{{ $item['url'] }}" wire:navigate class="inline-flex min-h-10 items-center text-sm hover:text-blue-700 dark:hover:text-blue-300">{{ $item['label'] }}</a></li>@endforeach</ul></div>@endif
                    <div><h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer care</h2><ul class="mt-4 space-y-2"><li><a href="{{ url('/account') }}" wire:navigate class="inline-flex min-h-10 items-center text-sm hover:text-blue-700">Your account</a></li><li><a href="{{ url('/cart') }}" wire:navigate class="inline-flex min-h-10 items-center text-sm hover:text-blue-700">Your cart</a></li><li><a href="{{ url('/search') }}" wire:navigate class="inline-flex min-h-10 items-center text-sm hover:text-blue-700">Search</a></li></ul></div>
                    <div><h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Store</h2><p class="mt-4 text-sm leading-6 text-slate-500">Secure checkout<br>Mock payments for demonstration<br>{{ $currentStore->default_currency }} pricing</p></div>
                </div>
                <div class="mt-10 flex flex-col gap-4 border-t border-slate-200 pt-6 text-xs text-slate-500 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"><p>&copy; {{ now()->year }} {{ $currentStore->name }}. All rights reserved.</p><div class="flex gap-3" aria-label="Accepted payment methods"><span>Visa</span><span>Mastercard</span><span>PayPal</span></div></div>
            </div>
            <script type="application/ld+json">{!! $organizationJson !!}</script>
        </footer>
    </x-slot:footer>

    <x-slot:cart><livewire:storefront.cart-drawer /></x-slot:cart>
    <x-slot:search><livewire:storefront.search.modal /></x-slot:search>

    <div x-data="{ toasts: [] }" @toast.window="const item={ id: Date.now()+Math.random(), type: $event.detail.type ?? 'info', message: $event.detail.message ?? '' }; toasts.push(item); setTimeout(() => toasts=toasts.filter(t => t.id !== item.id), 5000)" class="pointer-events-none fixed right-4 top-4 z-[100] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2" aria-live="polite" aria-atomic="false">
        <template x-for="toast in toasts" :key="toast.id"><div class="pointer-events-auto rounded-xl border-l-4 bg-white p-4 text-sm font-medium text-slate-900 shadow-xl dark:bg-slate-900 dark:text-white" :class="toast.type === 'success' ? 'border-emerald-500' : (toast.type === 'error' ? 'border-red-500' : 'border-blue-500')"><span x-text="toast.message"></span></div></template>
    </div>
</x-storefront.layout>
