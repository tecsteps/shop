@extends('admin.layout')

@section('title', 'Search Settings')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold text-slate-900">Search Index Configuration</h2>
    @if ($settings)
        <div class="mt-4 grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Synonyms</h3>
                <pre class="mt-2 overflow-x-auto rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($settings->synonyms_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Stop Words</h3>
                <pre class="mt-2 overflow-x-auto rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($settings->stop_words_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    @else
        <p class="mt-4 text-sm text-slate-600">No search settings configured for this store.</p>
    @endif
</div>
@endsection
