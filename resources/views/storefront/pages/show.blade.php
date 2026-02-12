@extends('storefront.layout')

@section('content')
<article class="mx-auto max-w-3xl space-y-5">
    <header>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">{{ $page->title }}</h1>
    </header>

    <div class="prose prose-sm max-w-none rounded-2xl border border-zinc-200 bg-white p-6 text-zinc-700 sm:prose-base">
        {!! $page->body_html !!}
    </div>
</article>
@endsection
