@extends('admin.layout')

@section('title', 'Create Collection')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Create Collection</h1>
    </div>

    <form method="POST" action="{{ route('admin.collections.store') }}" class="space-y-6">
        @csrf

        @include('admin.collections._form', ['collection' => null, 'selectedProductIds' => []])

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.collections.index') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                Cancel
            </a>
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                Save Collection
            </button>
        </div>
    </form>
@endsection
