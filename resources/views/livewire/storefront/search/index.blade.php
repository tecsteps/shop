<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Search results'],
    ]" />

    <h1 class="mt-6 text-3xl font-bold text-zinc-900 dark:text-white">
        @if($query)
            Search results for "{{ $query }}"
        @else
            Search
        @endif
    </h1>

    {{-- Stub: Search functionality will be implemented in Phase 8 --}}
    <div class="mt-16 flex flex-col items-center justify-center text-center">
        <svg class="h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <p class="mt-4 text-zinc-500 dark:text-zinc-400">
            Search functionality coming soon.
        </p>
    </div>
</div>
