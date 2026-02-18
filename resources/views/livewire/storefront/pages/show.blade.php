<div>
    <x-slot:title>{{ $page->title }}</x-slot:title>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">{{ $page->title }}</h1>

        <div class="prose prose-lg mt-8 max-w-none dark:prose-invert">
            {!! $page->body_html !!}
        </div>
    </div>
</div>
