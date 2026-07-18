<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => $page->title, 'url' => null],
    ]" />

    <h1 class="mt-4 text-3xl font-bold text-zinc-900 dark:text-white">{{ $page->title }}</h1>

    <div class="prose prose-zinc dark:prose-invert mt-6 max-w-none">
        {!! $page->body_html !!}
    </div>
</div>
