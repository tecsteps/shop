<x-storefront.layout :title="$page->title">
    <article class="prose prose-zinc mx-auto max-w-3xl px-4 py-10 dark:prose-invert">
        <h1>{{ $page->title }}</h1>
        {!! $page->body_html !!}
    </article>
</x-storefront.layout>

