<article class="mx-auto max-w-3xl space-y-8">
    <header class="space-y-3">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">{{ $page->title }}</h1>
    </header>

    <div class="prose prose-zinc max-w-none text-zinc-700 dark:prose-invert dark:text-zinc-300">
        {!! $page->body_html !!}
    </div>
</article>
