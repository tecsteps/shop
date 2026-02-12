@extends('admin.layout')

@section('title', 'Collections')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Collections</h1>
            <p class="mt-1 text-sm text-zinc-600">Group products manually or automatically.</p>
        </div>

        <a href="{{ route('admin.collections.create') }}" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
            Create Collection
        </a>
    </div>

    <form method="GET" class="mb-4 grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 md:grid-cols-[1fr_220px_auto]">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search title or handle..."
            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
        >

        <select name="status" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>
                    {{ ucfirst($statusOption->value) }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
            Filter
        </button>
    </form>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Collection</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Products</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($collections as $collection)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900">{{ $collection->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $collection->handle }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $collection->type }}</td>
                            <td class="px-4 py-3">{{ $collection->status->value }}</td>
                            <td class="px-4 py-3">{{ $collection->products_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.collections.edit', $collection) }}" class="text-sm font-medium text-zinc-900 hover:underline">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No collections found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3">
            {{ $collections->links() }}
        </div>
    </div>
@endsection
