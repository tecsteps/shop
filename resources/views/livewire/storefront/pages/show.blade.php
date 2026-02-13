<div>
    <h1 class="text-3xl font-bold tracking-tight">{{ $page->title }}</h1>

    @if($page->body_html)
        <div class="prose prose-zinc mt-8 max-w-none dark:prose-invert">
            {!! $page->body_html !!}
        </div>
    @endif
</div>
