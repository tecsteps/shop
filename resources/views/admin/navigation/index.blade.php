@extends('admin.layout')

@section('title', 'Navigation')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Navigation</h1>
        <p class="mt-1 text-sm text-zinc-600">Manage the <span class="font-medium">{{ $menu->title }}</span> items. Use position values to reorder.</p>
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Main Menu Items</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Label</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3">Resource ID</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-3" colspan="6">
                                <div class="grid gap-2 md:grid-cols-[1.3fr_150px_1.4fr_150px_110px_auto_auto]">
                                    <form method="POST" action="{{ route('admin.navigation.items.update', $item) }}" class="contents">
                                        @csrf
                                        @method('PUT')

                                        <input
                                            type="text"
                                            name="label"
                                            value="{{ $item->label }}"
                                            required
                                            class="w-full rounded-md border border-zinc-300 px-3 py-2"
                                        >

                                        <select name="type" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                                            @foreach ($types as $type)
                                                <option value="{{ $type->value }}" @selected($item->type->value === $type->value)>
                                                    {{ $type->value }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input
                                            type="text"
                                            name="url"
                                            value="{{ $item->url }}"
                                            placeholder="/collections/summer"
                                            class="w-full rounded-md border border-zinc-300 px-3 py-2"
                                        >

                                        <input
                                            type="number"
                                            name="resource_id"
                                            min="1"
                                            value="{{ $item->resource_id }}"
                                            class="w-full rounded-md border border-zinc-300 px-3 py-2"
                                        >

                                        <input
                                            type="number"
                                            name="position"
                                            min="0"
                                            value="{{ $item->position }}"
                                            required
                                            class="w-full rounded-md border border-zinc-300 px-3 py-2"
                                        >

                                        <button type="submit" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                                            Save
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.navigation.items.destroy', $item) }}" onsubmit="return confirm('Remove this navigation item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No navigation items yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-zinc-200 bg-white p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Add Item</h2>

        <form method="POST" action="{{ route('admin.navigation.items.store') }}" class="mt-4 grid gap-3 md:grid-cols-[1.3fr_150px_1.4fr_150px_110px_auto]">
            @csrf

            <input
                type="text"
                name="label"
                value="{{ old('label') }}"
                placeholder="Label"
                required
                class="w-full rounded-md border border-zinc-300 px-3 py-2"
            >

            <select name="type" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', \App\Enums\NavigationItemType::Link->value) === $type->value)>
                        {{ $type->value }}
                    </option>
                @endforeach
            </select>

            <input
                type="text"
                name="url"
                value="{{ old('url') }}"
                placeholder="/path or absolute URL"
                class="w-full rounded-md border border-zinc-300 px-3 py-2"
            >

            <input
                type="number"
                name="resource_id"
                min="1"
                value="{{ old('resource_id') }}"
                placeholder="Resource ID"
                class="w-full rounded-md border border-zinc-300 px-3 py-2"
            >

            <input
                type="number"
                name="position"
                min="0"
                value="{{ old('position', $nextPosition) }}"
                required
                class="w-full rounded-md border border-zinc-300 px-3 py-2"
            >

            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                Add
            </button>
        </form>

        <p class="mt-3 text-xs text-zinc-500">For `page`, `collection`, and `product` types, `resource_id` can auto-generate storefront URLs.</p>
    </section>
@endsection
