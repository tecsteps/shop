@extends('admin.layout')

@section('title', 'Themes')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($themes as $theme)
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-base font-semibold text-slate-900">{{ $theme->name }}</h2>
            <p class="mt-1 text-sm text-slate-600">Version {{ $theme->version }}</p>
            <p class="mt-2 text-sm text-slate-500">Status: {{ is_object($theme->status) ? $theme->status->value : $theme->status }}</p>
            <a href="{{ route('admin.themes.editor', $theme->id) }}" class="mt-4 inline-block text-sm font-medium text-slate-700 hover:text-slate-900">Open editor</a>
        </div>
    @empty
        <p class="text-sm text-slate-500">No themes available.</p>
    @endforelse
</div>
@endsection
