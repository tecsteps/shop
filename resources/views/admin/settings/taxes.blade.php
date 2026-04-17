@extends('admin.layout')

@section('title', 'Tax Settings')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-6">
    @if ($tax)
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Mode</dt><dd class="text-slate-900">{{ is_object($tax->mode) ? $tax->mode->value : $tax->mode }}</dd></div>
            <div><dt class="text-slate-500">Provider</dt><dd class="text-slate-900">{{ is_object($tax->provider) ? $tax->provider->value : $tax->provider }}</dd></div>
            <div><dt class="text-slate-500">Prices Include Tax</dt><dd class="text-slate-900">{{ $tax->prices_include_tax ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="text-slate-500">Updated</dt><dd class="text-slate-900">{{ optional($tax->updated_at)->toDateTimeString() ?? '—' }}</dd></div>
        </dl>
        <pre class="mt-4 overflow-x-auto rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($tax->config_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
    @else
        <p class="text-sm text-slate-600">No tax settings configured for this store.</p>
    @endif
</div>
@endsection
