@extends('admin.layout')

@section('title', 'Settings')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-900">Store Settings</h2>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Store Name</dt><dd class="text-slate-900">{{ $store->name }}</dd></div>
            <div><dt class="text-slate-500">Handle</dt><dd class="font-mono text-slate-900">{{ $store->handle }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="text-slate-900">{{ is_object($store->status) ? $store->status->value : $store->status }}</dd></div>
            <div><dt class="text-slate-500">Currency</dt><dd class="text-slate-900">{{ strtoupper($store->default_currency) }}</dd></div>
        </dl>

        @if ($settings)
            <h3 class="mt-6 text-sm font-semibold text-slate-900">JSON Settings</h3>
            <pre class="mt-2 overflow-x-auto rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($settings->settings_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        @endif
    </div>

    <div class="space-y-3">
        <a class="block rounded border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="{{ route('admin.settings.shipping') }}">Shipping Settings</a>
        <a class="block rounded border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="{{ route('admin.settings.taxes') }}">Tax Settings</a>
    </div>
</div>
@endsection
