@extends('storefront.layout')

@section('title', $page->title.' | '.($currentStore->name ?? config('app.name')))

@section('content')
    <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $page->title }}</h1>

        @if ($page->published_at)
            <p class="mt-2 text-xs uppercase tracking-wide text-slate-500">
                Published {{ $page->published_at->format('F j, Y') }}
            </p>
        @endif

        <div class="prose prose-slate mt-6 max-w-none">
            {!! $page->body_html !!}
        </div>
    </article>
@endsection
