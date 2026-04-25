<x-admin.layout :title="'Products'">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-bold tracking-normal">Products</h1><p class="text-zinc-600 dark:text-zinc-400">Search, filter, create, edit, and archive catalog products.</p></div>
        <a href="{{ route('admin.products.create') }}" class="rounded-md bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950">Create product</a>
    </div>
    <form method="GET" class="mt-6 flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search products" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
        <select name="status" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"><option value="">All statuses</option><option value="active">Active</option><option value="draft">Draft</option><option value="archived">Archived</option></select>
        <button class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700">Filter</button>
    </form>
    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"><tr><th class="p-3">Product</th><th class="p-3">Status</th><th class="p-3">Price</th><th class="p-3"></th></tr></thead>
            <tbody>
                @foreach($products as $product)
                    <tr class="border-t border-zinc-200 dark:border-zinc-800">
                        <td class="p-3 font-medium">{{ $product->title }}</td>
                        <td class="p-3">{{ $product->status->value }}</td>
                        <td class="p-3">@if($product->defaultVariant)<x-shop.price :amount="$product->defaultVariant->price_amount" :currency="$product->defaultVariant->currency" />@endif</td>
                        <td class="p-3 text-right">
                            <a class="underline" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.products.archive', $product) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="underline">Archive</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-admin.layout>

