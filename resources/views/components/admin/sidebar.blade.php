<aside class="hidden w-64 shrink-0 border-r border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 md:block">
    <div class="mb-6 text-lg font-semibold">
        {{ app()->bound('current_store') ? app('current_store')->name : 'Shop' }}
    </div>
    <nav class="space-y-1 text-sm">
        <a href="{{ route('admin.dashboard') }}" class="block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Dashboard</a>
        @if (Route::has('admin.products.index'))
            <a href="{{ route('admin.products.index') }}" class="block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Products</a>
        @endif
        @if (Route::has('admin.orders.index'))
            <a href="{{ route('admin.orders.index') }}" class="block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Orders</a>
        @endif
        @if (Route::has('admin.customers.index'))
            <a href="{{ route('admin.customers.index') }}" class="block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Customers</a>
        @endif
        @if (Route::has('admin.discounts.index'))
            <a href="{{ route('admin.discounts.index') }}" class="block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Discounts</a>
        @endif
        @if (Route::has('admin.collections.index'))
            <a href="{{ route('admin.collections.index') }}" class="block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Collections</a>
        @endif
        @if (Route::has('admin.settings.index'))
            <a href="{{ route('admin.settings.index') }}" class="block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">Settings</a>
        @endif
    </nav>
</aside>
