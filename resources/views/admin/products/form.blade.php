<x-admin.layout :title="$product->exists ? 'Edit product' : 'Create product'">
    <h1 class="text-3xl font-bold tracking-normal">{{ $product->exists ? 'Edit product' : 'Create product' }}</h1>
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="mt-6 grid max-w-3xl gap-5 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        @csrf
        @if($product->exists) @method('PATCH') @endif
        <label class="grid gap-2"><span>Title</span><input name="title" value="{{ old('title', $product->title) }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="grid gap-2"><span>Status</span><select name="status" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"><option @selected(old('status', $product->status?->value) === 'active') value="active">Active</option><option @selected(old('status', $product->status?->value) === 'draft') value="draft">Draft</option><option @selected(old('status', $product->status?->value) === 'archived') value="archived">Archived</option></select></label>
        <label class="grid gap-2"><span>Price</span><input name="price_amount" type="number" step="0.01" value="{{ old('price_amount', $product->defaultVariant ? $product->defaultVariant->price_amount / 100 : '24.99') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="grid gap-2"><span>Description</span><textarea name="description_html" rows="6" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">{{ old('description_html', $product->description_html) }}</textarea></label>
        <button class="rounded-md bg-zinc-950 px-4 py-3 font-medium text-white dark:bg-white dark:text-zinc-950">Save product</button>
    </form>
</x-admin.layout>

