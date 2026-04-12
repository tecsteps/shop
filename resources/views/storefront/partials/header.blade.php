@php
    $mainMenu = null;
    if (isset($store) && $store) {
        $mainMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'main-menu')
            ->with(['items' => fn ($q) => $q->orderBy('position')])
            ->first();
    }
    $navItems = $mainMenu
        ? app(\App\Services\NavigationService::class)->buildTree($mainMenu)
        : [];
@endphp

<header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/80">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('storefront.home') }}" class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
            {{ $store->name ?? config('app.name') }}
        </a>

        <nav class="hidden items-center gap-6 md:flex" aria-label="Main navigation">
            @forelse ($navItems as $item)
                <a href="{{ $item['url'] }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                    {{ $item['label'] }}
                </a>
            @empty
                <a href="{{ route('storefront.home') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Shop</a>
            @endforelse
        </nav>

        <div class="flex items-center gap-4">
            <button type="button" aria-label="Search" class="rounded-full p-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.374 9.86l3.133 3.133a.75.75 0 1 0 1.06-1.06l-3.133-3.133A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" /></svg>
            </button>

            <a href="#" aria-label="Account" class="rounded-full p-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" /></svg>
            </a>

            <button type="button" aria-label="Cart" class="relative rounded-full p-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M1 1.75A.75.75 0 0 1 1.75 1h1.628a1.75 1.75 0 0 1 1.734 1.51L5.18 3a65.25 65.25 0 0 1 13.36 1.412.75.75 0 0 1 .58.875 48.645 48.645 0 0 1-1.618 6.2.75.75 0 0 1-.712.513H6a2.503 2.503 0 0 0-2.292 1.5H17.25a.75.75 0 0 1 0 1.5H2.76a.75.75 0 0 1-.748-.807 4.002 4.002 0 0 1 2.716-3.486L3.626 2.716a.25.25 0 0 0-.248-.216H1.75A.75.75 0 0 1 1 1.75ZM6 17.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM15.5 17.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z" /></svg>
                <span class="absolute -right-0.5 -top-0.5 hidden h-4 min-w-[1rem] items-center justify-center rounded-full bg-zinc-900 px-1 text-[10px] font-semibold text-white dark:bg-white dark:text-zinc-900" data-cart-count>0</span>
            </button>
        </div>
    </div>
</header>
