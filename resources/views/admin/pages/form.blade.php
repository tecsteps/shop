@extends('admin.layout')

@section('title', $mode === 'create' ? 'Create Page' : 'Edit Page')

@section('content')
@php
    $isEdit = $mode === 'edit' && $page !== null;
    $formAction = '#';

    if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.pages.update')) {
        $formAction = route('admin.pages.update', ['page' => $page->id]);
    }

    if (! $isEdit && \Illuminate\Support\Facades\Route::has('admin.pages.store')) {
        $formAction = route('admin.pages.store');
    }

    $publishedAt = old('published_at');

    if ($publishedAt === null && $page?->published_at !== null) {
        $publishedAt = $page->published_at->format('Y-m-d\\TH:i');
    }
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? 'Edit Page' : 'Create Page' }}</h2>
    <p class="mt-2 text-sm text-slate-600">Manage static page content and publication status.</p>

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
            Page submission route is not wired yet.
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
                <input id="title" name="title" type="text" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('title', $page?->title) }}">
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="handle" class="block text-sm font-medium text-slate-700">Handle</label>
                <input id="handle" name="handle" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('handle', $page?->handle) }}" placeholder="auto-generated-from-title">
                @error('handle')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['draft', 'published', 'archived'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $page ? (is_object($page->status) ? $page->status->value : $page->status) : 'draft') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="published_at" class="block text-sm font-medium text-slate-700">Published At</label>
                <input id="published_at" name="published_at" type="datetime-local" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ $publishedAt }}">
                @error('published_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="body_html" class="block text-sm font-medium text-slate-700">Body (HTML)</label>
            <textarea id="body_html" name="body_html" rows="12" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm">{{ old('body_html', $page?->body_html) }}</textarea>
            @error('body_html')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
            <a href="{{ route('admin.pages.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back</a>

            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400" @disabled($formAction === '#')>
                {{ $isEdit ? 'Save Page' : 'Create Page' }}
            </button>
        </div>
    </form>

    @if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.pages.destroy'))
        <form method="POST" action="{{ route('admin.pages.destroy', ['page' => $page->id]) }}" class="mt-4" onsubmit="return confirm('Delete this page?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                Delete Page
            </button>
        </form>
    @endif
</div>
@endsection
