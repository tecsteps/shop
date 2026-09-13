<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => $page->title]]" />
    <h1 class="mt-4 text-3xl font-bold">{{ $page->title }}</h1>
    <div class="prose mt-4 max-w-3xl dark:prose-invert">{!! $page->body_html !!}</div>
</div>
