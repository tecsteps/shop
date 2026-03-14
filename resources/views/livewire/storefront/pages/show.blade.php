<div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <x-storefront.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('storefront.home')],
            ['label' => $page->title],
        ]" class="mb-6" />

        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-8">
            {{ $page->title }}
        </h1>

        @if ($page->content_html)
            <div class="prose dark:prose-invert max-w-none">
                {!! $page->content_html !!}
            </div>
        @endif
    </div>
</div>
