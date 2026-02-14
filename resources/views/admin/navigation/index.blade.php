@extends('admin.layout')

@section('title', 'Navigation')

@section('content')
<div class="space-y-4">
    @forelse ($menus as $menu)
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-base font-semibold text-slate-900">{{ $menu->title }}</h2>
            <p class="mt-1 font-mono text-xs text-slate-500">{{ $menu->handle }}</p>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($menu->items as $item)
                    <li class="rounded border border-slate-200 px-3 py-2">{{ $item->title }} <span class="text-slate-500">({{ $item->type }}: {{ $item->target }})</span></li>
                @empty
                    <li class="text-slate-500">No menu items.</li>
                @endforelse
            </ul>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600">No navigation menus configured.</div>
    @endforelse
</div>
@endsection
