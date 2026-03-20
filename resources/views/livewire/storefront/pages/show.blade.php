<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumbs --}}
        @include('storefront.components.breadcrumbs', ['items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => $page->title],
        ]])

        <h1 class="mt-6 text-3xl font-bold text-zinc-900 dark:text-white">{{ $page->title }}</h1>

        @if($page->body_html)
            <div class="prose dark:prose-invert mt-6 max-w-none">
                {!! $page->body_html !!}
            </div>
        @endif
    </div>
</div>
