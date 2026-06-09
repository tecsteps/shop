<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('Collections')],
    ]" />

    <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
        {{ __('Collections') }}
    </h1>

    @if ($collections->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="size-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <p class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">{{ __('No collections yet') }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Check back soon for curated collections.') }}</p>
        </div>
    @else
        <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($collections as $collection)
                @include('storefront.partials.collection-card', ['collection' => $collection])
            @endforeach
        </div>
    @endif
</div>
