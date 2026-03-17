<div>
    @foreach($sections as $section)
        @if($section === 'hero')
            {{-- Hero Banner --}}
            <section class="relative flex min-h-[300px] items-center justify-center bg-gradient-to-br from-gray-900 to-gray-700 px-4 py-16 text-white sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]">
                @if(! empty($heroSettings['image']))
                    <img src="{{ $heroSettings['image'] }}"
                         alt=""
                         class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-black/50"></div>
                @endif
                <div class="relative mx-auto max-w-3xl text-center">
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl lg:text-6xl">
                        {{ $heroSettings['heading'] ?? 'Welcome to Our Store' }}
                    </h1>
                    @if(! empty($heroSettings['subheading']))
                        <p class="mt-4 text-base text-white/80 sm:text-lg md:mt-6 md:text-xl">
                            {{ $heroSettings['subheading'] }}
                        </p>
                    @endif
                    @if(! empty($heroSettings['cta_text']))
                        <div class="mt-8">
                            <a href="{{ $heroSettings['cta_link'] ?? '/collections' }}"
                               class="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:text-base">
                                {{ $heroSettings['cta_text'] }}
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if($section === 'featured_collections')
            {{-- Featured Collections --}}
            <section class="mx-auto max-w-7xl bg-white px-4 py-16 dark:bg-gray-950 sm:px-6 lg:px-8">
                <h2 class="mb-8 text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
                    Shop by Collection
                </h2>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                    {{-- Placeholder cards when no collections exist yet --}}
                    @for($i = 0; $i < 4; $i++)
                        <div class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-gray-200 dark:bg-gray-800">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <div class="absolute bottom-0 left-0 p-4">
                                <p class="text-sm font-semibold text-white">Coming Soon</p>
                                <p class="mt-1 text-xs text-white/70 underline">Shop now</p>
                            </div>
                        </div>
                    @endfor
                </div>
            </section>
        @endif

        @if($section === 'featured_products')
            {{-- Featured Products --}}
            <section class="mx-auto max-w-7xl bg-white px-4 py-16 dark:bg-gray-950 sm:px-6 lg:px-8">
                <h2 class="mb-8 text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
                    Featured Products
                </h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                    {{-- Skeleton placeholders --}}
                    @for($i = 0; $i < 4; $i++)
                        <div class="animate-pulse">
                            <div class="aspect-square rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                            <div class="mt-3 h-4 w-3/4 rounded bg-gray-200 dark:bg-gray-800"></div>
                            <div class="mt-2 h-4 w-1/2 rounded bg-gray-200 dark:bg-gray-800"></div>
                        </div>
                    @endfor
                </div>
            </section>
        @endif

        @if($section === 'newsletter')
            {{-- Newsletter Signup --}}
            <section class="bg-gray-100 px-4 py-16 dark:bg-gray-900">
                <div class="mx-auto max-w-xl text-center">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">
                        Stay in the loop
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Subscribe for exclusive offers and updates.
                    </p>
                    <form class="mt-6 flex gap-3">
                        <label for="newsletter-email" class="sr-only">Email address</label>
                        <input type="email"
                               id="newsletter-email"
                               placeholder="Enter your email"
                               required
                               class="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500">
                        <button type="submit"
                                class="rounded-md bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-500">
                            Subscribe
                        </button>
                    </form>
                </div>
            </section>
        @endif

        @if($section === 'rich_text')
            {{-- Rich Text Section --}}
            <section class="mx-auto max-w-3xl bg-white px-4 py-16 dark:bg-gray-950 sm:px-6 lg:px-8">
                <div class="prose dark:prose-invert mx-auto">
                    <p class="text-gray-600 dark:text-gray-400">
                        Quality products, exceptional service, and fast shipping. That is what we stand for.
                    </p>
                </div>
            </section>
        @endif
    @endforeach
</div>
