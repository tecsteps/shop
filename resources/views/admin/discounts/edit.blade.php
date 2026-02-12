@extends('admin.layout')

@section('title', 'Edit Discount')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Edit Discount</h1>
        <p class="mt-1 text-sm text-zinc-600">{{ $discount->code ?: 'Automatic discount' }}</p>
    </div>

    <form method="POST" action="{{ route('admin.discounts.update', $discount) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.discounts._form')

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.discounts.index') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                Back
            </a>
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                Save Changes
            </button>
        </div>
    </form>
@endsection
