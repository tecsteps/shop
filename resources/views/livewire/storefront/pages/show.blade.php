<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <header class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $page->title }}</h1>
    </header>

    <div class="prose prose-neutral max-w-none dark:prose-invert">
        {!! $page->body_html !!}
    </div>
</div>
