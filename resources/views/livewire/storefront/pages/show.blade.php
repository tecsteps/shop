<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => $page->title],
    ]" />

    <article class="mt-6">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
            {{ $page->title }}
        </h1>
        <div class="sf-prose mt-6">
            {!! $page->body_html !!}
        </div>
    </article>
</div>
