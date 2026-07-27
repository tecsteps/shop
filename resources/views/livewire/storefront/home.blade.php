<div>
    @foreach ($sections as $section)
        @if ($section === 'hero')
            {{-- Hero banner --}}
            <section class="relative flex min-h-[300px] items-center justify-center overflow-hidden bg-gray-900 sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]">
                @if (! empty($hero['image_url']))
                    <img src="{{ $hero['image_url'] }}" alt="" class="absolute inset-0 size-full object-cover">
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-800 via-gray-900 to-black dark:from-gray-950 dark:via-black dark:to-gray-900" aria-hidden="true"></div>
                @endif
                <div class="absolute inset-0 bg-black/50" aria-hidden="true"></div>
                <div class="relative z-10 mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8">
                    <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ $hero['heading'] ?? app('current_store')->name }}
                    </h1>
                    @if (! empty($hero['subheading']))
                        <p class="mx-auto mt-6 max-w-xl text-lg text-gray-200">{{ $hero['subheading'] }}</p>
                    @endif
                    @if (! empty($hero['cta_label']) && ! empty($hero['cta_url']))
                        <a href="{{ $hero['cta_url'] }}"
                           class="mt-8 inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            {{ $hero['cta_label'] }}
                        </a>
                    @endif
                </div>
            </section>
        @elseif ($section === 'featured_collections')
            {{-- Featured collections --}}
            <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="featured-collections-heading">
                <h2 id="featured-collections-heading" class="text-center text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">Featured collections</h2>
                @if ($featuredCollections->isNotEmpty())
                    <div class="mt-10 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                        @foreach ($featuredCollections as $collection)
                            <a href="{{ route('storefront.collections.show', ['handle' => $collection->handle]) }}"
                               aria-label="{{ $collection->title }}"
                               class="group relative block aspect-[3/4] overflow-hidden rounded-lg bg-gray-200 transition-transform duration-300 hover:scale-[1.02] focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:bg-gray-800">
                                <div class="absolute inset-0 bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-700 dark:to-gray-800" aria-hidden="true"></div>
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-4 pt-12">
                                    <span class="block text-base font-semibold text-white sm:text-lg">{{ $collection->title }}</span>
                                    <span class="mt-1 inline-block text-sm text-white/80 underline underline-offset-2 transition group-hover:text-white">Shop now</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @elseif ($section === 'featured_products')
            {{-- Featured products --}}
            <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="featured-products-heading">
                <h2 id="featured-products-heading" class="text-center text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">Featured products</h2>
                @if ($featuredProducts->isNotEmpty())
                    <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($featuredProducts as $product)
                            <x-storefront::product-card :product="$product" wire:key="featured-product-{{ $product->id }}" />
                        @endforeach
                    </div>
                @endif
            </section>
        @elseif ($section === 'newsletter')
            {{-- Newsletter signup (client-side only until subscriptions are implemented) --}}
            <section class="bg-gray-100 dark:bg-gray-900" aria-labelledby="newsletter-heading">
                <div class="mx-auto max-w-xl px-4 py-16 text-center sm:px-6 lg:px-8"
                     x-data="{ subscribed: false, email: '' }">
                    <h2 id="newsletter-heading" class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">Stay in the loop</h2>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">Subscribe for exclusive offers and updates.</p>
                    <div aria-live="polite">
                        <template x-if="!subscribed">
                            <form @submit.prevent="subscribed = true" class="mt-6 flex gap-3">
                                <label for="newsletter-email" class="sr-only">Email address</label>
                                <input id="newsletter-email" type="email" x-model="email" required placeholder="Enter your email"
                                       class="min-w-0 flex-1 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <button type="submit"
                                        class="shrink-0 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                    Subscribe
                                </button>
                            </form>
                        </template>
                        <template x-if="subscribed">
                            <p class="mt-6 text-sm font-medium text-green-600 dark:text-green-400">Thanks for subscribing!</p>
                        </template>
                    </div>
                </div>
            </section>
        @elseif ($section === 'rich_text' && ! empty($richTextHtml))
            {{-- Rich text --}}
            <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="storefront-prose text-gray-700 dark:text-gray-300">
                    {!! app(\App\Actions\SanitizeHtml::class)($richTextHtml) !!}
                </div>
            </section>
        @endif
    @endforeach
</div>
