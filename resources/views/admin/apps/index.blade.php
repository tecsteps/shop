@extends('admin.layout')

@section('title', 'Apps')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-base font-semibold text-slate-900">Installed Apps</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse ($installations as $installation)
                <li class="rounded border border-slate-200 px-3 py-2">
                    <a href="{{ route('admin.apps.show', $installation->id) }}" class="font-medium text-slate-800 hover:text-slate-900">{{ $installation->app?->name ?? ('App #'.$installation->app_id) }}</a>
                    <span class="ml-2 text-slate-500">{{ is_object($installation->status) ? $installation->status->value : $installation->status }}</span>
                </li>
            @empty
                <li class="text-slate-500">No apps installed.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-base font-semibold text-slate-900">Available Apps</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse ($availableApps as $app)
                <li class="rounded border border-slate-200 px-3 py-2">{{ $app->name }} <span class="text-slate-500">({{ is_object($app->status) ? $app->status->value : $app->status }})</span></li>
            @empty
                <li class="text-slate-500">No apps registered.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
