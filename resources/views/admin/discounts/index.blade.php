@extends('admin.layout')

@section('title', 'Discounts')

@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('admin.discounts.create') }}" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">New Discount</a>
</div>

<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr><th class="px-4 py-2 text-left">Code</th><th class="px-4 py-2 text-left">Type</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-right">Usage</th><th class="px-4 py-2 text-right">Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($discounts as $discount)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ $discount->code ?? 'Automatic' }}</td>
                    <td class="px-4 py-2">{{ is_object($discount->value_type) ? $discount->value_type->value : $discount->value_type }}</td>
                    <td class="px-4 py-2">{{ is_object($discount->status) ? $discount->status->value : $discount->status }}</td>
                    <td class="px-4 py-2 text-right">{{ (int) $discount->usage_count }} / {{ $discount->usage_limit ?? '∞' }}</td>
                    <td class="px-4 py-2 text-right">
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('admin.discounts.edit', $discount->id) }}" class="text-slate-700 hover:text-slate-900">Edit</a>

                            @if (\Illuminate\Support\Facades\Route::has('admin.discounts.destroy'))
                                <form method="POST" action="{{ route('admin.discounts.destroy', ['discount' => $discount->id]) }}" onsubmit="return confirm('Delete this discount?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:text-rose-900">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No discounts found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $discounts->links() }}</div>
@endsection
