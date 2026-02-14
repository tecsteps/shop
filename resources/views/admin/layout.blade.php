<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | {{ $store->name ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<div class="flex min-h-screen">
    <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white p-4 lg:block">
        <a href="{{ route('admin.dashboard') }}" class="mb-4 block text-lg font-semibold text-slate-900">{{ $store->name ?? 'Admin' }}</a>
        <nav class="space-y-1 text-sm">
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.products.index') }}">Products</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.collections.index') }}">Collections</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.inventory.index') }}">Inventory</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.orders.index') }}">Orders</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.customers.index') }}">Customers</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.discounts.index') }}">Discounts</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.settings.index') }}">Settings</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.themes.index') }}">Themes</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.pages.index') }}">Pages</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.navigation.index') }}">Navigation</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.apps.index') }}">Apps</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.developers.index') }}">Developers</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.analytics.index') }}">Analytics</a>
            <a class="block rounded px-2 py-1 hover:bg-slate-100" href="{{ route('admin.search.settings') }}">Search Settings</a>
        </nav>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <h1 class="text-base font-semibold text-slate-900">@yield('title', 'Admin')</h1>
                <form method="POST" action="{{ route('admin.auth.logout') }}">
                    @csrf
                    <button type="submit" class="rounded border border-slate-300 px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
