
@php
    $homeSettings = data_get($settings ?? [], 'home', []);
    $sectionOrder = data_get($settings ?? [], 'home_sections', ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text']);
    $sectionOrder = is_array($sectionOrder) && count($sectionOrder) ? $sectionOrder : ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text'];
    $collectionCount = max(2, min(4, (int) data_get($homeSettings, 'featured_collections.count', 4)));
    $featuredProductCount = max(4, min(8, (int) data_get($homeSettings, 'featured_products.count', 8)));
    $hasCollections = is_countable($collections ?? null) && count($collections) > 0;
    $homeTitle = data_get($homeSettings, 'hero.heading', 'Make room for what matters.');
    $homeSubheading = data_get($homeSettings, 'hero.subheading', 'Considered essentials, made to be worn and loved every day.');
    $heroImage = data_get($homeSettings, 'hero.image_url');
    $heroCta = data_get($homeSettings, 'hero.cta_text', 'Explore the collection');
    $heroUrl = data_get($homeSettings, 'hero.cta_url', '/collections');
@endphp

@section('meta_description', data_get($settings ?? [], 'seo.home_description', $homeSubheading))

<div>
@foreach ($sectionOrder as $section)
        @if (is_array($section) && (data_get($section, 'visible') === false || data_get($section, 'enabled') === false))
            @continue
        @endif
        @php($sectionName = str_replace('-', '_', is_array($section) ? data_get($section, 'type', '') : $section))
        @switch($sectionName)
            @case('hero')
                @if (data_get($homeSettings, 'hero.enabled', true))
                    <section aria-labelledby="storefront-hero-heading" class="relative isolate flex min-h-[25rem] items-center justify-center overflow-hidden bg-stone-900 px-4 py-20 text-center text-white sm:min-h-[31.25rem] sm:px-8 lg:min-h-[37.5rem]">
                        @if ($heroImage)<img src="{{ $heroImage }}" alt="" fetchpriority="high" class="absolute inset-0 -z-20 size-full object-cover">@endif
                        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-stone-950/45 via-stone-950/45 to-stone-950/70"></div>
                        <div class="mx-auto max-w-3xl">
                            @if (data_get($homeSettings, 'hero.eyebrow'))<p class="mb-5 text-xs font-semibold uppercase tracking-[0.24em] text-white/80">{{ data_get($homeSettings, 'hero.eyebrow') }}</p>@endif
                            <h1 id="storefront-hero-heading" class="text-balance text-4xl font-semibold tracking-tight sm:text-5xl lg:text-7xl">{{ $homeTitle }}</h1>
                            <p class="mx-auto mt-6 max-w-2xl text-pretty text-base leading-7 text-white/85 sm:text-lg sm:leading-8">{{ $homeSubheading }}</p>
                            <a href="{{ $heroUrl }}" class="mt-8 inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-white px-7 text-sm font-semibold text-stone-950 transition hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                                {{ $heroCta }} @include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-4'])
                            </a>
                        </div>
                    </section>
                @endif
                @break
            @case('featured_collections')
                @if (data_get($homeSettings, 'featured_collections.enabled', true) && $hasCollections)
                    <section aria-labelledby="featured-collections-heading" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                        <div class="mb-7 flex items-end justify-between gap-5 sm:mb-9">
                            <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Find your next favorite</p><h2 id="featured-collections-heading" class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{{ data_get($homeSettings, 'featured_collections.heading', 'Shop by collection') }}</h2></div>
                            <a href="/collections" class="hidden min-h-11 items-center gap-2 text-sm font-semibold text-stone-700 underline-offset-4 hover:underline sm:inline-flex dark:text-stone-200">View all @include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-4'])</a>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                            @foreach (collect($collections)->take($collectionCount) as $collection)
                                @php($collectionTitle = data_get($collection, 'title', 'Collection'))
                                @php($collectionImage = data_get($collection, 'image_url', data_get($collection, 'image.src')))
                                <a href="{{ url('/collections/'.data_get($collection, 'handle', '')) }}" class="group relative aspect-[3/4] overflow-hidden rounded-2xl bg-stone-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-700 dark:bg-stone-800" aria-label="{{ $collectionTitle }} collection">
                                    @if ($collectionImage)<img src="{{ $collectionImage }}" alt="{{ data_get($collection, 'image_alt', $collectionTitle) }}" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transition-none">@else<span class="flex size-full items-center justify-center text-stone-400">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-12'])</span>@endif
                                    <span class="absolute inset-0 bg-gradient-to-t from-stone-950/75 via-stone-950/5 to-transparent"></span>
                                    <span class="absolute inset-x-0 bottom-0 p-4 text-white sm:p-5"><span class="block text-base font-semibold sm:text-lg">{{ $collectionTitle }}</span><span class="mt-1 inline-flex items-center gap-1 text-sm text-white/80 underline decoration-white/50 underline-offset-4 transition group-hover:text-white">Shop now @include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-3.5'])</span></span>
                                </a>
                            @endforeach
                        </div>
                        <a href="/collections" class="mt-5 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-stone-700 underline-offset-4 hover:underline sm:hidden dark:text-stone-200">View all collections @include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-4'])</a>
                    </section>
                @endif
                @break
            @case('featured_products')
                @if (data_get($homeSettings, 'featured_products.enabled', true))
                    <section aria-labelledby="featured-products-heading" class="bg-stone-50 py-14 dark:bg-stone-900/60 sm:py-20">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div class="mb-7 flex items-end justify-between gap-5 sm:mb-9">
                                <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Made for everyday</p><h2 id="featured-products-heading" class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{{ data_get($homeSettings, 'featured_products.heading', 'A few favorites') }}</h2></div>
                                <a href="/collections" class="hidden min-h-11 items-center gap-2 text-sm font-semibold underline-offset-4 hover:underline sm:inline-flex">Shop all @include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-4'])</a>
                            </div>
                            @if (is_countable($featuredProducts ?? null) && count($featuredProducts) > 0)
                                <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-5 lg:grid-cols-4 lg:gap-x-6">
                                    @foreach (collect($featuredProducts)->take($featuredProductCount) as $product)
                                        @include('storefront.components.product-card', ['product' => $product, 'headingLevel' => 'h3'])
                                    @endforeach
                                </div>
                            @elseif (!isset($featuredProducts))
                                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4" aria-label="Featured products loading">
                                    @for ($skeleton = 0; $skeleton < 4; $skeleton++)<div class="animate-pulse" aria-hidden="true"><div class="aspect-square rounded-2xl bg-stone-200 dark:bg-stone-800"></div><div class="mt-3 h-4 w-3/4 rounded bg-stone-200 dark:bg-stone-800"></div><div class="mt-2 h-4 w-1/3 rounded bg-stone-200 dark:bg-stone-800"></div></div>@endfor
                                </div>
                            @else
                                <p class="rounded-2xl border border-dashed border-stone-300 px-5 py-10 text-center text-sm text-stone-600 dark:border-stone-700 dark:text-stone-300">New favorites are on their way.</p>
                            @endif
                        </div>
                    </section>
                @endif
                @break
            @case('newsletter')
                @if (data_get($homeSettings, 'newsletter.enabled', true))
                    @if (class_exists(\App\Livewire\Storefront\NewsletterSignup::class))
                        @livewire('storefront.newsletter-signup')
                    @else
                        <section aria-labelledby="newsletter-heading" class="bg-blue-50 px-4 py-14 dark:bg-blue-950/35 sm:px-6 sm:py-16">
                            <div class="mx-auto max-w-2xl text-center"><h2 id="newsletter-heading" class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ data_get($homeSettings, 'newsletter.heading', 'Stay in the loop') }}</h2><p class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300">{{ data_get($homeSettings, 'newsletter.subheading', 'Subscribe for thoughtful notes, new arrivals, and occasional offers.') }}</p>
                                <form wire:submit="subscribe" class="mx-auto mt-6 flex max-w-lg flex-col gap-3 sm:flex-row"><label for="newsletter-email" class="sr-only">Email address</label><input id="newsletter-email" name="email" type="email" wire:model="email" autocomplete="email" required placeholder="Enter your email" class="storefront-input min-w-0 flex-1"><button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-stone-950 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-white dark:text-stone-950 dark:hover:bg-blue-200"><span wire:loading.remove wire:target="subscribe">Subscribe</span><span wire:loading wire:target="subscribe">Subscribing…</span></button></form><p class="mt-2 text-left text-sm text-rose-700 dark:text-rose-300">@error('email'){{ $message }}@enderror</p><p class="sr-only" role="status" aria-live="polite">{{ session('newsletter-success') }}</p>
                            </div>
                        </section>
                    @endif
                @endif
                @break
            @case('rich_text')
                @if (data_get($homeSettings, 'rich_text.enabled', false) && data_get($homeSettings, 'rich_text.html'))
                    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6 sm:py-20"><div class="storefront-prose">{!! $safeRichText !!}</div></section>
                @endif
                @break
        @endswitch
    @endforeach
</div>
