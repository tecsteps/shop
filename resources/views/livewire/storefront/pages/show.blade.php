<div>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.components.breadcrumbs :items="[
            ['label' => 'Home', 'url' => '/'],
            ['label' => $page->title, 'url' => null],
        ]" class="mb-6" />

        <h1 class="mb-8 text-3xl font-bold text-gray-900 dark:text-white">
            {{ $page->title }}
        </h1>

        @if($page->body_html)
            <div class="prose max-w-none text-gray-600 dark:prose-invert dark:text-gray-400">
                {!! $page->body_html !!}
            </div>
        @endif
    </div>
</div>
