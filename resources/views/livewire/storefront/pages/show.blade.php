<div class="mx-auto max-w-3xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => $page->title],
    ]" />

    <article class="prose prose-zinc max-w-none dark:prose-invert">
        <h1>{{ $page->title }}</h1>
        {!! $page->body_html !!}
    </article>
</div>
