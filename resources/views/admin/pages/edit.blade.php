@extends('admin.layout')

@section('title', 'Edit Page')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Edit Page</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ $page->title }}</p>
        </div>

        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                Delete Page
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.pages._form', ['page' => $page])

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pages.index') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                Back
            </a>
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                Save Changes
            </button>
        </div>
    </form>
@endsection
