<article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-normal">{{ $page->title }}</h1>
    <div class="prose prose-zinc mt-6 max-w-none dark:prose-invert">
        {!! $page->body_html !!}
    </div>
</article>
