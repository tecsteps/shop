<div>
    @foreach($sectionOrder as $section)
        @php $sectionSettings = data_get($themeSettings, "sections.{$section}", []); @endphp

        @if(data_get($sectionSettings, 'enabled', false))
            @switch($section)
                @case('hero')
                    {{-- Hero Banner --}}
                    <section class="relative flex min-h-[400px] items-center justify-center overflow-hidden bg-zinc-900 lg:min-h-[600px]">
                        @if(data_get($sectionSettings, 'background_image'))
                            <img src="{{ data_get($sectionSettings, 'background_image') }}"
                                 alt=""
                                 class="absolute inset-0 h-full w-full object-cover">
                        @endif
                        <div class="absolute inset-0 bg-black/40"></div>
                        <div class="relative z-10 px-4 text-center text-white">
                            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                {{ data_get($sectionSettings, 'heading', 'Welcome') }}
                            </h1>
                            @if(data_get($sectionSettings, 'subheading'))
                                <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-200 sm:text-xl">
                                    {{ data_get($sectionSettings, 'subheading') }}
                                </p>
                            @endif
                            @if(data_get($sectionSettings, 'cta_text'))
                                <a href="{{ data_get($sectionSettings, 'cta_link', '/collections') }}"
                                   class="mt-8 inline-block rounded-md bg-white px-8 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100">
                                    {{ data_get($sectionSettings, 'cta_text') }}
                                </a>
                            @endif
                        </div>
                    </section>
                    @break

                @case('featured_collections')
                    {{-- Featured Collections --}}
                    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Collections</h2>
                        <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            @foreach($this->featuredCollections as $collection)
                                <a href="/collections/{{ $collection->handle }}" class="group relative overflow-hidden rounded-lg">
                                    <div class="aspect-[3/4] bg-zinc-200 dark:bg-zinc-700">
                                        <div class="flex h-full items-end p-4">
                                            <div>
                                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $collection->title }}</h3>
                                                <span class="mt-1 text-sm text-zinc-600 group-hover:underline dark:text-zinc-400">Shop now</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                    @break

                @case('featured_products')
                    {{-- Featured Products --}}
                    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Featured Products</h2>
                        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach($this->featuredProducts as $product)
                                @include('storefront.components.product-card', ['product' => $product])
                            @endforeach
                        </div>
                    </section>
                    @break

                @case('newsletter')
                    {{-- Newsletter --}}
                    <section class="bg-zinc-50 py-16 dark:bg-zinc-800">
                        <div class="mx-auto max-w-xl px-4 text-center">
                            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Stay in the loop</h2>
                            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Subscribe for exclusive offers and updates.</p>
                            <form class="mt-6 flex gap-2" x-data="{ submitted: false, email: '' }" @submit.prevent="submitted = true">
                                <template x-if="!submitted">
                                    <div class="flex w-full gap-2">
                                        <input x-model="email" type="email" required placeholder="Enter your email"
                                               class="flex-1 rounded-md border border-zinc-300 px-4 py-2 text-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                        <button type="submit"
                                                class="rounded-md bg-zinc-900 px-6 py-2 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                                            Subscribe
                                        </button>
                                    </div>
                                </template>
                                <template x-if="submitted">
                                    <p class="w-full text-sm font-medium text-emerald-600 dark:text-emerald-400">Thanks for subscribing!</p>
                                </template>
                            </form>
                        </div>
                    </section>
                    @break

                @case('rich_text')
                    {{-- Rich Text --}}
                    @if(data_get($sectionSettings, 'content'))
                        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                            <div class="prose dark:prose-invert max-w-none">
                                {!! data_get($sectionSettings, 'content') !!}
                            </div>
                        </section>
                    @endif
                    @break
            @endswitch
        @endif
    @endforeach
</div>
