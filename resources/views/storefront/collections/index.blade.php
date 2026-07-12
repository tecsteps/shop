<div class="sf-container sf-page-y">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => url('/')], ['label' => 'Collections']]" />
    <header class="mt-7 max-w-2xl">
        <p class="sf-eyebrow">Browse by story</p>
        <h1 class="sf-page-title">Collections</h1>
        <p class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-300">Explore pieces grouped to make finding your next favorite easy.</p>
    </header>

    @if ($collections->isEmpty())
        <div class="sf-empty-state mt-12">
            <svg aria-hidden="true" class="mx-auto size-12 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 8-9-5-9 5 9 5 9-5Zm0 0v8l-9 5m0-8v8m0-8L3 8m0 0v8l9 5"/></svg>
            <h2 class="mt-4 text-xl font-semibold">Collections are coming soon</h2>
            <p class="mt-2 text-slate-500">Please check back shortly.</p>
        </div>
    @else
        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                @php($media = optional(optional($collection->products->first())->media->first())->storage_key)
                <a href="{{ url('/collections/'.$collection->handle) }}" wire:navigate class="group sf-card overflow-hidden p-0">
                    <div class="aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
                        @if ($media)
                            <img src="{{ Storage::disk('public')->url($media) }}" alt="{{ $collection->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 motion-safe:group-hover:scale-105">
                        @else
                            <div class="grid h-full place-items-center bg-gradient-to-br from-slate-100 to-blue-50 dark:from-slate-800 dark:to-blue-950"><span class="text-4xl font-semibold text-slate-300 dark:text-slate-600">{{ str($collection->title)->substr(0, 1) }}</span></div>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-4 p-5">
                        <div>
                            <h2 class="text-lg font-semibold group-hover:text-blue-700 dark:group-hover:text-blue-300">{{ $collection->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $collection->products_count }} {{ str('product')->plural($collection->products_count) }}</p>
                        </div>
                        <span aria-hidden="true" class="text-xl text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-600">&rarr;</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
