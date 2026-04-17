<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => $page->title],
    ]" />

    <h1 class="mt-6 text-3xl font-bold text-zinc-900 dark:text-white">{{ $page->title }}</h1>

    @if($page->body_html)
        <div class="mt-8 prose dark:prose-invert max-w-3xl">
            {!! $page->body_html !!}
        </div>
    @endif
</div>
