@php
    $adminStore = app()->bound('current_store') ? app('current_store') : null;
    $adminUser = auth()->user();
    $activeRoute = request()->route()?->getName() ?? '';
    $navigationGroups = [
        ['label' => 'Overview', 'items' => [['label' => 'Dashboard', 'href' => '/admin', 'route' => 'admin.dashboard', 'icon' => 'chart-bar']]],
        ['label' => 'Catalog', 'items' => [
            ['label' => 'Products', 'href' => '/admin/products', 'route' => 'admin.products*', 'icon' => 'cube'],
            ['label' => 'Collections', 'href' => '/admin/collections', 'route' => 'admin.collections*', 'icon' => 'rectangle-stack'],
        ]],
        ['label' => 'Sales', 'items' => [
            ['label' => 'Orders', 'href' => '/admin/orders', 'route' => 'admin.orders*', 'icon' => 'shopping-bag'],
            ['label' => 'Customers', 'href' => '/admin/customers', 'route' => 'admin.customers*', 'icon' => 'users'],
            ['label' => 'Discounts', 'href' => '/admin/discounts', 'route' => 'admin.discounts*', 'icon' => 'tag'],
        ]],
        ['label' => 'Store', 'items' => [
            ['label' => 'Pages', 'href' => '/admin/pages', 'route' => 'admin.pages*', 'icon' => 'document-text'],
            ['label' => 'Themes', 'href' => '/admin/themes', 'route' => 'admin.themes*', 'icon' => 'paint-brush'],
            ['label' => 'Analytics', 'href' => '/admin/analytics', 'route' => 'admin.analytics*', 'icon' => 'chart-pie'],
            ['label' => 'Settings', 'href' => '/admin/settings', 'route' => 'admin.settings*', 'icon' => 'cog-6-tooth'],
            ['label' => 'Search settings', 'href' => '/admin/search/settings', 'route' => 'admin.search.settings', 'icon' => 'magnifying-glass'],
            ['label' => 'Developers', 'href' => '/admin/developers', 'route' => 'admin.developers*', 'icon' => 'code-bracket'],
        ]],
    ];
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Admin · '.config('app.name')])
    <style>[x-cloak] { display: none !important; }</style>
    <script>
        (() => {
            const preference = localStorage.getItem('theme');
            const dark = preference ? preference === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-100" x-data="{
    mobileOpen: false,
    toasts: [],
    addToast(detail) {
        const toast = { id: Date.now() + Math.random(), type: detail?.type || 'info', message: detail?.message || '' };
        if (!toast.message) return;
        this.toasts.push(toast);
        window.setTimeout(() => this.toasts = this.toasts.filter(item => item.id !== toast.id), 5000);
    }
}" x-on:toast.window="addToast($event.detail)">
    <a href="#admin-main" class="sr-only z-[100] rounded-lg bg-white px-4 py-3 font-semibold text-zinc-950 shadow-lg focus:fixed focus:left-4 focus:top-4 focus:not-sr-only focus:outline-2 focus:outline-offset-2 focus:outline-blue-600 dark:bg-zinc-900 dark:text-white">Skip to main content</a>

    <button type="button" x-cloak x-show="mobileOpen" x-transition.opacity x-on:click="mobileOpen = false" class="fixed inset-0 z-40 bg-zinc-950/50 lg:hidden" aria-label="Close navigation menu"></button>

    <aside id="admin-navigation" x-cloak x-trap.noscroll="mobileOpen" x-on:keydown.escape.window="mobileOpen = false" x-bind:class="mobileOpen ? 'translate-x-0 visible' : '-translate-x-full invisible lg:translate-x-0 lg:visible'" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-zinc-200 bg-white transition-transform duration-200 dark:border-zinc-800 dark:bg-zinc-900 lg:w-64">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-zinc-200 px-5 dark:border-zinc-800">
            <a href="/admin" wire:navigate class="flex min-w-0 flex-1 items-center gap-3" aria-label="{{ config('app.name') }} admin home">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white"><flux:icon name="shopping-bag" class="size-5" /></span>
                <span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ config('app.name', 'Shop') }}</span><span class="block text-xs text-zinc-500 dark:text-zinc-400">Store admin</span></span>
            </a>
            <button type="button" x-on:click="mobileOpen = false" class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 lg:hidden" aria-label="Close navigation menu"><flux:icon name="x-mark" class="size-5" /></button>
        </div>

        @include('livewire.admin.components.navigation', ['groups' => $navigationGroups, 'activeRoute' => $activeRoute])

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            <a href="/admin/settings" wire:navigate class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">
                <flux:icon name="lifebuoy" class="size-5 shrink-0" /> Help & support
            </a>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-zinc-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8 dark:border-zinc-800 dark:bg-zinc-950/90">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" x-on:click="mobileOpen = true" x-bind:aria-expanded="mobileOpen.toString()" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Open navigation menu" aria-controls="admin-navigation"><flux:icon name="bars-3" class="size-5" /></button>
                <div class="min-w-0">
                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">Current store</span>
                    <span class="block truncate text-sm font-semibold">{{ data_get($adminStore, 'name', 'Store') }}</span>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <button type="button" x-on:click="const dark = !document.documentElement.classList.contains('dark'); document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')" class="inline-flex size-11 items-center justify-center rounded-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Toggle dark mode">
                    <flux:icon name="sun" class="size-5 dark:hidden" /><flux:icon name="moon" class="hidden size-5 dark:block" />
                </button>
                <div class="hidden text-right sm:block"><span class="block max-w-40 truncate text-sm font-medium">{{ $adminUser?->name ?? 'Administrator' }}</span><span class="block max-w-40 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $adminUser?->email ?? '' }}</span></div>
                <span class="inline-flex size-10 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-800 dark:bg-blue-950 dark:text-blue-200" aria-hidden="true">{{ collect(explode(' ', $adminUser?->name ?? 'A'))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('') }}</span>
                <a href="/admin/logout" wire:navigate class="hidden min-h-10 items-center rounded-lg px-3 text-sm font-medium text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 md:inline-flex dark:text-zinc-300 dark:hover:bg-zinc-800">Log out</a>
            </div>
        </header>

        <main id="admin-main" class="mx-auto min-h-[calc(100vh-4rem)] max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8" tabindex="-1">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100" role="status" aria-live="polite"><flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0" />{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>

    <div class="fixed right-4 top-4 z-[100] grid w-[min(24rem,calc(100vw-2rem))] gap-3" aria-live="polite" aria-atomic="false">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition class="rounded-xl border bg-white p-4 shadow-xl dark:bg-zinc-900" x-bind:class="toast.type === 'success' ? 'border-l-4 border-l-emerald-500 border-zinc-200 dark:border-zinc-700' : (toast.type === 'error' ? 'border-l-4 border-l-rose-500 border-zinc-200 dark:border-zinc-700' : 'border-l-4 border-l-blue-500 border-zinc-200 dark:border-zinc-700')" x-bind:role="toast.type === 'error' ? 'alert' : 'status'"><div class="flex items-start gap-3"><p class="min-w-0 flex-1 text-sm" x-text="toast.message"></p><button type="button" x-on:click="toasts = toasts.filter(item => item.id !== toast.id)" class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:bg-zinc-800" aria-label="Dismiss notification"><flux:icon name="x-mark" class="size-4" /></button></div></div>
        </template>
    </div>

    @fluxScripts
</body>
</html>
