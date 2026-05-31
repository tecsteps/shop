<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('storefront.home')],
        ['label' => __('Collections')],
    ]" />

    <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Collections') }}</h1>

    @if ($collections->isEmpty())
        <div class="mt-12 rounded-xl border border-dashed border-zinc-300 py-16 text-center dark:border-zinc-700">
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No collections are available yet.') }}</p>
        </div>
    @else
        <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($collections as $collection)
                <a href="/collections/{{ $collection->handle }}" wire:navigate
                   class="group relative block aspect-[3/4] overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/70 to-transparent transition group-hover:from-zinc-900/80"></div>
                    <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                        <h2 class="text-lg font-semibold">{{ $collection->title }}</h2>
                        <span class="text-sm text-white/80 underline transition group-hover:text-white">{{ __('Shop now') }} &rarr;</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
