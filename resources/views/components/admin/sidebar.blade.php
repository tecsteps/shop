@php
    $link = function (string $name, string $label) {
        if (! \Illuminate\Support\Facades\Route::has($name)) {
            return '';
        }

        $active = request()->routeIs($name) || request()->routeIs(str_replace('.index', '.*', $name));
        $classes = 'block rounded-md px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800';
        if ($active) {
            $classes .= ' bg-zinc-100 font-medium dark:bg-zinc-800';
        }

        return '<a href="'.route($name).'" class="'.$classes.'" wire:navigate>'.e($label).'</a>';
    };
@endphp
<aside class="hidden w-64 shrink-0 border-r border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 md:block">
    <div class="mb-6 text-lg font-semibold">
        {{ app()->bound('current_store') ? app('current_store')->name : 'Shop' }}
    </div>
    <nav class="space-y-1 text-sm">
        {!! $link('admin.dashboard', 'Dashboard') !!}
        {!! $link('admin.orders.index', 'Orders') !!}
        {!! $link('admin.products.index', 'Products') !!}
        {!! $link('admin.collections.index', 'Collections') !!}
        {!! $link('admin.customers.index', 'Customers') !!}
        {!! $link('admin.discounts.index', 'Discounts') !!}
        {!! $link('admin.analytics.index', 'Analytics') !!}
        <div class="mt-4 text-xs font-semibold uppercase text-zinc-500">Content</div>
        {!! $link('admin.pages.index', 'Pages') !!}
        {!! $link('admin.navigation.index', 'Navigation') !!}
        {!! $link('admin.themes.index', 'Themes') !!}
        <div class="mt-4 text-xs font-semibold uppercase text-zinc-500">System</div>
        {!! $link('admin.settings.index', 'Settings') !!}
        {!! $link('admin.search.settings', 'Search') !!}
        {!! $link('admin.apps.index', 'Apps') !!}
        {!! $link('admin.developers.index', 'Developers') !!}
    </nav>
</aside>
