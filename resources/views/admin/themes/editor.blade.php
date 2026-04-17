@extends('admin.layout')

@section('title', 'Theme Editor')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-900">{{ $theme->name }}</h2>
        <p class="mt-2 text-sm text-slate-600">Theme file inventory and settings snapshot.</p>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse ($theme->files as $file)
                <li class="rounded border border-slate-200 px-3 py-2">{{ $file->path }} <span class="text-slate-500">({{ is_object($file->type) ? $file->type->value : $file->type }})</span></li>
            @empty
                <li class="text-slate-500">No theme files stored.</li>
            @endforelse
        </ul>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-slate-900">Settings</h3>
        <pre class="mt-2 overflow-x-auto rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($theme->settings?->settings_json ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
@endsection
