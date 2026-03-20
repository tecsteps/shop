<div>
    {{-- Hero banner --}}
    @if($settings['hero_heading'] ?? null)
        <section class="relative flex min-h-[300px] items-center justify-center bg-gray-900 px-4 py-20 text-center text-white sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]"
                 @if($settings['hero_image'] ?? null)
                     style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('{{ $settings['hero_image'] }}'); background-size: cover; background-position: center;"
                 @endif>
            <div class="mx-auto max-w-3xl">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl xl:text-6xl">
                    {{ $settings['hero_heading'] }}
                </h1>
                @if($settings['hero_subheading'] ?? null)
                    <p class="mt-4 text-base text-white/80 sm:mt-6 sm:text-lg lg:text-xl">
                        {{ $settings['hero_subheading'] }}
                    </p>
                @endif
                @if($settings['hero_cta_text'] ?? null)
                    <div class="mt-8">
                        <a href="{{ $settings['hero_cta_link'] ?? '/' }}"
                           class="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-colors">
                            {{ $settings['hero_cta_text'] }}
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Featured collections --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <h2 class="text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">Featured Collections</h2>
        <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
            {{-- Collections will be loaded from database once Phase 2 is complete --}}
        </div>
    </section>

    {{-- Featured products --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <h2 class="text-center text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">Featured Products</h2>
        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            {{-- Products will be loaded from database once Phase 2 is complete --}}
        </div>
    </section>

    {{-- Newsletter signup --}}
    <section class="bg-gray-100 dark:bg-gray-800/50">
        <div class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6 lg:px-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white lg:text-2xl">Stay in the loop</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Subscribe for exclusive offers and updates.</p>
            <form class="mt-6 flex gap-3" x-data="{ subscribed: false }" x-show="!subscribed">
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input type="email"
                       id="newsletter-email"
                       placeholder="Enter your email"
                       required
                       class="min-w-0 flex-1 rounded-md border border-gray-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500">
                <button type="submit"
                        class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-colors">
                    Subscribe
                </button>
            </form>
        </div>
    </section>

    {{-- Rich text section --}}
    @if($settings['rich_text_content'] ?? null)
        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="prose dark:prose-invert max-w-none">
                {!! $settings['rich_text_content'] !!}
            </div>
        </section>
    @endif
</div>
