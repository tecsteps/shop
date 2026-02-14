@extends('admin.layout')

@section('title', $mode === 'create' ? 'Create Collection' : 'Edit Collection')

@section('content')
@php
    $isEdit = $mode === 'edit' && $collection !== null;
    $formAction = '#';

    if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.collections.update')) {
        $formAction = route('admin.collections.update', ['collection' => $collection->id]);
    }

    if (! $isEdit && \Illuminate\Support\Facades\Route::has('admin.collections.store')) {
        $formAction = route('admin.collections.store');
    }

    $linkedProductIds = old('product_ids');

    if ($linkedProductIds === null && $collection !== null) {
        $linkedProductIds = $collection->products->pluck('id')->implode(', ');
    }
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? 'Edit Collection' : 'Create Collection' }}</h2>
    <p class="mt-2 text-sm text-slate-600">Manage collection metadata and sync product membership by comma-separated IDs.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($formAction === '#')
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Collection submission route is not wired yet.
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="mt-6 space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                <input id="title" name="title" type="text" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('title', $collection?->title) }}">
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="handle" class="block text-sm font-medium text-slate-700">Handle</label>
                <input id="handle" name="handle" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('handle', $collection?->handle) }}" placeholder="auto-generated-from-title">
                @error('handle')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-slate-700">Type</label>
                <select id="type" name="type" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['manual', 'automated'] as $type)
                        <option value="{{ $type }}" @selected(old('type', $collection ? (is_object($collection->type) ? $collection->type->value : $collection->type) : 'manual') === $type)>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
                @error('type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['draft', 'active', 'archived'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $collection ? (is_object($collection->status) ? $collection->status->value : $collection->status) : 'active') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="product_ids" class="block text-sm font-medium text-slate-700">Product IDs</label>
            <input id="product_ids" name="product_ids" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ $linkedProductIds }}" placeholder="12, 24, 42">
            <p class="mt-1 text-xs text-slate-500">Only products from this store are synced.</p>
            @error('product_ids')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description_html" class="block text-sm font-medium text-slate-700">Description (HTML)</label>
            <textarea id="description_html" name="description_html" rows="8" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm">{{ old('description_html', $collection?->description_html) }}</textarea>
            @error('description_html')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
            <a href="{{ route('admin.collections.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back</a>

            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400" @disabled($formAction === '#')>
                {{ $isEdit ? 'Save Collection' : 'Create Collection' }}
            </button>
        </div>
    </form>

    @if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.collections.destroy'))
        <form method="POST" action="{{ route('admin.collections.destroy', ['collection' => $collection->id]) }}" class="mt-4" onsubmit="return confirm('Delete this collection?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                Delete Collection
            </button>
        </form>
    @endif
</div>
@endsection
