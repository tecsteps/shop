@extends('admin.layout')

@section('title', 'Pages')

@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('admin.pages.create') }}" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">New Page</a>
</div>
<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-2 text-left">Title</th><th class="px-4 py-2 text-left">Handle</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-right">Actions</th></tr></thead>
        <tbody>
            @forelse ($pages as $page)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ $page->title }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $page->handle }}</td>
                    <td class="px-4 py-2">{{ is_object($page->status) ? $page->status->value : $page->status }}</td>
                    <td class="px-4 py-2 text-right">
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('admin.pages.edit', $page->id) }}" class="text-slate-700 hover:text-slate-900">Edit</a>

                            @if (\Illuminate\Support\Facades\Route::has('admin.pages.destroy'))
                                <form method="POST" action="{{ route('admin.pages.destroy', ['page' => $page->id]) }}" onsubmit="return confirm('Delete this page?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:text-rose-900">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No pages found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $pages->links() }}</div>
@endsection
