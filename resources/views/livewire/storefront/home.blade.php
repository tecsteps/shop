<div>
    @foreach($sections as $section)
        @continue(! $section['enabled'])

        @switch($section['key'])
            @case('hero')
                <section wire:key="home-section-hero" class="bg-zinc-950 text-white dark:bg-black">
                    <div class="mx-auto grid min-h-[24rem] max-w-7xl gap-8 px-4 py-16 sm:min-h-[31rem] sm:px-6 lg:min-h-[38rem] lg:grid-cols-[1fr_0.8fr] lg:px-8 lg:py-24">
                        <div class="flex flex-col justify-center gap-6">
                            <h1 class="max-w-3xl text-4xl font-semibold tracking-normal sm:text-5xl">{{ data_get($settings, 'home.hero_heading') }}</h1>
                            <p class="max-w-2xl text-lg leading-8 text-zinc-300">{{ data_get($settings, 'home.hero_subheading') }}</p>
                            <div>
                                <a href="{{ data_get($settings, 'home.hero_cta_url') }}" class="inline-flex rounded-md bg-white px-5 py-3 text-sm font-semibold text-zinc-950 hover:bg-zinc-200">
                                    {{ data_get($settings, 'home.hero_cta_label') }}
                                </a>
                            </div>
                        </div>
                        <div class="min-h-72 rounded-lg bg-[linear-gradient(135deg,#f8fafc,#bae6fd,#bbf7d0)] p-6 text-zinc-950 shadow-2xl dark:bg-[linear-gradient(135deg,#18181b,#0f766e,#1d4ed8)] dark:text-white">
                            <div class="flex h-full flex-col justify-end gap-3">
                                <div class="text-sm font-medium uppercase tracking-normal text-zinc-600 dark:text-zinc-300">{{ $store->name }}</div>
                                <div class="text-2xl font-semibold">{{ data_get($settings, 'home.hero_heading') }}</div>
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case('featured_collections')
                <section wire:key="home-section-featured-collections" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-normal">{{ data_get($settings, 'home.featured_collections_heading') }}</h2>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ data_get($settings, 'home.featured_collections_subheading') }}</p>
                        </div>
                        <a href="/collections" class="text-sm font-semibold hover:underline">View all</a>
                    </div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($collections as $collection)
                            <a wire:key="home-collection-{{ $collection->id }}" href="/collections/{{ $collection->handle }}" class="group relative overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 p-5 transition hover:-translate-y-0.5 dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="absolute inset-0 bg-[linear-gradient(135deg,#e0f2fe,#fef3c7,#dcfce7)] opacity-80 transition group-hover:scale-105 dark:bg-[linear-gradient(135deg,#0f172a,#134e4a,#312e81)]"></div>
                                <div class="relative flex aspect-[3/4] flex-col justify-end">
                                    <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $collection->title }}</h3>
                                    <div class="mt-2 text-sm font-medium text-zinc-700 underline underline-offset-4 dark:text-zinc-200">Shop now</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
                @break

            @case('featured_products')
                <section wire:key="home-section-featured-products" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div class="flex items-end justify-between gap-4">
                        <h2 class="text-2xl font-semibold tracking-normal">{{ data_get($settings, 'home.featured_products_heading') }}</h2>
                        <a href="/search" class="text-sm font-semibold hover:underline">Search products</a>
                    </div>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($products as $product)
                            <div wire:key="home-product-{{ $product->id }}">
                                @include('storefront.components.product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                </section>
                @break

            @case('newsletter')
                <section wire:key="home-section-newsletter" class="bg-zinc-50 px-4 py-14 dark:bg-zinc-900 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-2xl font-semibold tracking-normal">{{ data_get($settings, 'home.newsletter_heading') }}</h2>
                        <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ data_get($settings, 'home.newsletter_subheading') }}</p>

                        @if($newsletterSubscribed)
                            <p class="mt-6 rounded-md bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300" role="status">Thanks for subscribing.</p>
                        @else
                            <form wire:submit="subscribeToNewsletter" class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto]">
                                <label for="newsletter-email" class="sr-only">Email address</label>
                                <input id="newsletter-email" wire:model="newsletterEmail" type="email" autocomplete="email" placeholder="Enter your email" class="min-h-11 rounded-md border border-zinc-300 bg-white px-4 text-sm text-zinc-950 outline-none focus:ring-2 focus:ring-zinc-950 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:focus:ring-white">
                                <button type="submit" class="min-h-11 rounded-md bg-zinc-950 px-5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-zinc-950" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Subscribe</span>
                                    <span wire:loading>Subscribing...</span>
                                </button>
                                @error('newsletterEmail')
                                    <p class="text-left text-sm text-red-600 dark:text-red-400 sm:col-span-2">{{ $message }}</p>
                                @enderror
                            </form>
                        @endif
                    </div>
                </section>
                @break

            @case('rich_text')
                @if(data_get($settings, 'home.rich_text_html'))
                    <section wire:key="home-section-rich-text" class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-semibold tracking-normal">{{ data_get($settings, 'home.rich_text_heading') }}</h2>
                        <div class="prose prose-zinc mt-5 max-w-none dark:prose-invert">
                            {!! data_get($settings, 'home.rich_text_html') !!}
                        </div>
                    </section>
                @endif
                @break
        @endswitch
    @endforeach
</div>
