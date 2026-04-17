<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumbs --}}
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('storefront.home')],
            ['label' => $page->title, 'url' => null],
        ]" />

        <article class="mx-auto mt-8 max-w-3xl">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">{{ $page->title }}</h1>
            <div class="prose mt-8 max-w-none dark:prose-invert">
                {!! $page->body_html !!}
            </div>
        </article>
    </div>
</div>
