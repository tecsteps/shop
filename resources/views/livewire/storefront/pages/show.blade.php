<div>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.breadcrumbs :items="[['label' => $title]]" />

        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>

        <div class="prose dark:prose-invert mt-8 max-w-none">
            {!! $bodyHtml !!}
        </div>
    </div>
</div>
