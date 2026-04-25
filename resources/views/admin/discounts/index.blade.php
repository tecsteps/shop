<x-admin.layout :title="'Discounts'">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-bold tracking-normal">Discounts</h1><p class="text-zinc-600 dark:text-zinc-400">Percentage, fixed amount, and free shipping codes.</p></div>
    </div>
    <form method="POST" action="{{ route('admin.discounts.store') }}" class="mt-6 grid gap-3 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-4">
        @csrf
        <label class="grid gap-1"><span>Code</span><input name="code" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <label class="grid gap-1"><span>Type</span><select name="type" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"><option value="percentage">Percentage</option><option value="fixed_amount">Fixed amount</option><option value="free_shipping">Free shipping</option></select></label>
        <label class="grid gap-1"><span>Value</span><input name="value" type="number" step="0.01" value="10" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
        <button class="self-end rounded-md bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950">Save discount</button>
    </form>
    <div class="mt-6 grid gap-3">
        @foreach($discounts as $discount)
            <div class="flex flex-wrap justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <span class="font-medium">{{ $discount->code }}</span>
                <span>{{ $discount->type->value }} {{ $discount->is_active ? 'active' : 'inactive' }}</span>
            </div>
        @endforeach
    </div>
</x-admin.layout>

