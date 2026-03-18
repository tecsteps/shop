<div>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => '/'], ['label' => $page->title]]" />

        <h1 class="mt-4 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">{{ $page->title }}</h1>

        @if($page->body_html)
            <div class="mt-6 prose prose-zinc max-w-none dark:prose-invert">
                {!! $page->body_html !!}
            </div>
        @endif
    </div>
</div>
