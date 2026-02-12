@extends('admin.layout')

@section('title', 'Search Settings')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Search Settings</h1>
        <p class="mt-1 text-sm text-zinc-600">Manage synonyms, stop words, and search index refresh.</p>
    </div>

    <form method="POST" action="{{ route('admin.search.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Synonyms</h2>
            <p class="mt-1 text-xs text-zinc-500">One line per synonym group (example: <code>sneakers, trainers, running shoes</code>).</p>

            <textarea
                name="synonyms"
                rows="10"
                class="mt-3 w-full rounded-md border border-zinc-300 px-3 py-2 font-mono text-sm"
            >{{ old('synonyms', $synonymsText) }}</textarea>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Stop Words</h2>
            <p class="mt-1 text-xs text-zinc-500">One word per line (example: <code>the</code>, <code>and</code>, <code>for</code>).</p>

            <textarea
                name="stop_words"
                rows="8"
                class="mt-3 w-full rounded-md border border-zinc-300 px-3 py-2 font-mono text-sm"
            >{{ old('stop_words', $stopWordsText) }}</textarea>
        </section>

        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
            Save Search Settings
        </button>
    </form>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <form method="POST" action="{{ route('admin.search.settings.reindex') }}">
            @csrf
            <button type="submit" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                Reindex Now
            </button>
        </form>

        <span class="text-xs text-zinc-500">
            Service status: {{ $searchServiceAvailable ? 'SearchService detected' : 'SearchService not available in this build' }}
        </span>
    </div>
@endsection
