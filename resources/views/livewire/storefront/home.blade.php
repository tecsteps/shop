<div>
    @foreach($sections as $section)
        @if(!empty($section['enabled']))
            @switch($section['type'])
                @case('hero')
                    {{-- Hero Banner --}}
                    <section class="relative flex min-h-[300px] sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px] items-center justify-center overflow-hidden bg-zinc-900">
                        @if(!empty($hero['background_image']))
                            <img src="{{ $hero['background_image'] }}" alt="" class="absolute inset-0 h-full w-full object-cover" aria-hidden="true">
                        @endif
                        <div class="absolute inset-0 bg-black/40"></div>
                        <div class="relative z-10 mx-auto max-w-7xl px-4 py-16 text-center text-white sm:px-6 lg:px-8">
                            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl lg:text-6xl">
                                {{ $hero['heading'] ?? 'Welcome to our store' }}
                            </h1>
                            @if(!empty($hero['subheading']))
                                <p class="mx-auto mt-4 max-w-2xl text-base sm:text-lg md:text-xl text-white/80">
                                    {{ $hero['subheading'] }}
                                </p>
                            @endif
                            @if(!empty($hero['cta_text']))
                                <a href="{{ $hero['cta_link'] ?? '/collections' }}"
                                   class="mt-8 inline-block rounded-md bg-blue-600 px-8 py-3 text-base font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                                    {{ $hero['cta_text'] }}
                                </a>
                            @endif
                        </div>
                    </section>
                    @break

                @case('featured_collections')
                    {{-- Featured Collections --}}
                    @if($featuredCollections->isNotEmpty())
                        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                            <h2 class="text-center text-2xl font-bold text-zinc-900 dark:text-white lg:text-3xl">
                                Collections
                            </h2>
                            <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                                @foreach($featuredCollections as $collection)
                                    <a href="{{ route('storefront.collections.show', $collection->handle) }}"
                                       class="group relative block overflow-hidden rounded-lg aspect-[3/4]">
                                        <div class="absolute inset-0 bg-zinc-200 dark:bg-zinc-700"></div>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                        <div class="absolute bottom-0 left-0 right-0 p-4">
                                            <h3 class="text-lg font-semibold text-white">{{ $collection->title }}</h3>
                                            <span class="mt-1 text-sm text-white/70 underline group-hover:text-white/100 transition-colors">
                                                Shop now
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                    @break

                @case('featured_products')
                    {{-- Featured Products --}}
                    @if($featuredProducts->isNotEmpty())
                        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                            <h2 class="text-center text-2xl font-bold text-zinc-900 dark:text-white lg:text-3xl">
                                Featured Products
                            </h2>
                            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                                @foreach($featuredProducts as $product)
                                    <x-storefront.product-card :product="$product" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                    @break

                @case('newsletter')
                    {{-- Newsletter --}}
                    <section class="bg-zinc-100 dark:bg-zinc-900 py-16">
                        <div class="mx-auto max-w-xl px-4 text-center sm:px-6">
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white sm:text-2xl">
                                Stay in the loop
                            </h2>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                Subscribe for exclusive offers and updates.
                            </p>
                            <form class="mt-6 flex gap-3" onsubmit="event.preventDefault()">
                                <label for="newsletter-email" class="sr-only">Email address</label>
                                <input type="email"
                                       id="newsletter-email"
                                       placeholder="Enter your email"
                                       required
                                       class="flex-1 rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-4 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit"
                                        class="shrink-0 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                                    Subscribe
                                </button>
                            </form>
                        </div>
                    </section>
                    @break

                @case('rich_text')
                    {{-- Rich Text --}}
                    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $themeSettings->get('rich_text_content', '') !!}
                        </div>
                    </section>
                    @break
            @endswitch
        @endif
    @endforeach
</div>
