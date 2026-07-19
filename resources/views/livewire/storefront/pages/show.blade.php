<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => $page->title],
    ]" />

    <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">{{ $page->title }}</h1>

    @if (! empty($page->body_html))
        <div class="storefront-prose mt-8 text-gray-700 dark:text-gray-300">
            {!! $page->body_html !!}
        </div>
    @endif
</div>
