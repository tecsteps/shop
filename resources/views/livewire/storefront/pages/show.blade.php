<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => $page->title, 'url' => ''],
    ]" />

    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-6">{{ $page->title }}</h1>

    @if($page->body_html)
        <div class="prose prose-lg dark:prose-invert max-w-none">
            {!! $page->body_html !!}
        </div>
    @endif
</div>
