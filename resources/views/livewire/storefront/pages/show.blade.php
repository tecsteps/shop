<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => $page->title],
    ]" class="mb-6" />

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $page->title }}</h1>

    <div class="prose mt-8 max-w-none dark:prose-invert">
        {!! $page->content !!}
    </div>
</div>
