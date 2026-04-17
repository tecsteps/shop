@php
    $footerMenu = null;
    if (app()->bound('current_store')) {
        $store = app('current_store');
        $footerMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'footer-menu')
            ->with(['items' => fn ($q) => $q->orderBy('position')])
            ->first();
    }
    $footerItems = $footerMenu
        ? app(\App\Services\NavigationService::class)->buildTree($footerMenu)
        : [];
@endphp
<footer class="mt-16 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
            <nav class="flex flex-wrap items-center gap-6" aria-label="Footer navigation">
                @foreach ($footerItems as $item)
                    <a href="{{ $item['url'] }}" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <p class="text-xs text-zinc-500 dark:text-zinc-500">{{ $footerText }}</p>
        </div>
    </div>
</footer>
