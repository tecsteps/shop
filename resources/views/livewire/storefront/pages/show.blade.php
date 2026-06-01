<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('storefront.home')],
        ['label' => $page->title],
    ]" />

    <article class="mt-6">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">{{ $page->title }}</h1>

        @if ($page->body_html)
            <div class="prose prose-zinc mt-6 max-w-none dark:prose-invert">
                {!! $page->body_html !!}
            </div>
        @endif
    </article>
</div>
