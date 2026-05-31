<div>
    @foreach ($sections as $section)
        @switch($section)
            @case('hero')
                <section class="relative flex min-h-[400px] items-center justify-center overflow-hidden bg-zinc-900 sm:min-h-[500px] lg:min-h-[600px]"
                         @if (! empty($hero['image_url'])) style="background-image: url('{{ $hero['image_url'] }}'); background-size: cover; background-position: center;" @endif>
                    <div class="absolute inset-0 bg-zinc-900/50"></div>
                    <div class="relative mx-auto max-w-3xl px-6 py-20 text-center text-white">
                        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">{{ $hero['heading'] ?? '' }}</h1>
                        @if (! empty($hero['subheading']))
                            <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-200">{{ $hero['subheading'] }}</p>
                        @endif
                        @if (! empty($hero['cta_label']) && ! empty($hero['cta_url']))
                            <a href="{{ $hero['cta_url'] }}" wire:navigate
                               class="mt-8 inline-flex items-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-zinc-900 transition hover:bg-zinc-100">
                                {{ $hero['cta_label'] }}
                            </a>
                        @endif
                    </div>
                </section>
                @break

            @case('featured_collections')
                @if ($featuredCollections->isNotEmpty())
                    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                        <h2 class="mb-8 text-center text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">{{ __('Shop by collection') }}</h2>
                        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                            @foreach ($featuredCollections as $collection)
                                <a href="/collections/{{ $collection->handle }}" wire:navigate
                                   class="group relative block aspect-[3/4] overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/70 to-transparent"></div>
                                    <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                                        <h3 class="text-lg font-semibold">{{ $collection->title }}</h3>
                                        <span class="text-sm text-white/80 underline transition group-hover:text-white">{{ __('Shop now') }} &rarr;</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
                @break

            @case('featured_products')
                @if ($featuredProducts->isNotEmpty())
                    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                        <h2 class="mb-8 text-center text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">{{ __('Featured products') }}</h2>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
                            @foreach ($featuredProducts as $product)
                                <x-storefront::product-card :product="$product" />
                            @endforeach
                        </div>
                    </section>
                @endif
                @break

            @case('newsletter')
                <section class="bg-zinc-50 py-16 dark:bg-zinc-900">
                    <div class="mx-auto max-w-xl px-6 text-center">
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $newsletter['heading'] ?? __('Stay in the loop') }}</h2>
                        <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $newsletter['subtext'] ?? '' }}</p>
                        {{-- The NewsletterSignup Livewire component is wired in task #6. --}}
                        <form class="mt-6 flex gap-2" onsubmit="return false">
                            <label for="newsletter-email" class="sr-only">{{ __('Email') }}</label>
                            <input id="newsletter-email" type="email" placeholder="{{ __('Enter your email') }}" required
                                   class="flex-1 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 font-medium text-white transition hover:bg-blue-700">{{ __('Subscribe') }}</button>
                        </form>
                    </div>
                </section>
                @break

            @case('rich_text')
                @if (! empty($richText))
                    <section class="mx-auto max-w-3xl px-6 py-16">
                        <div class="prose prose-zinc max-w-none dark:prose-invert">
                            {!! $richText !!}
                        </div>
                    </section>
                @endif
                @break
        @endswitch
    @endforeach
</div>
