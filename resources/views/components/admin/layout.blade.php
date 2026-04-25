<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} - {{ app('current_store')->name ?? 'Shop' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 font-sans text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <div class="lg:grid lg:grid-cols-[256px_1fr]">
        <aside class="border-r border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 lg:min-h-screen">
            <a href="{{ route('admin.dashboard') }}" class="block text-lg font-bold tracking-normal">{{ app('current_store')->name }}</a>
            <nav class="mt-6 grid gap-1 text-sm">
                @foreach([
                    ['Dashboard', 'admin.dashboard'],
                    ['Products', 'admin.products.index'],
                    ['Collections', 'admin.collections.index'],
                    ['Inventory', 'admin.inventory.index'],
                    ['Orders', 'admin.orders.index'],
                    ['Customers', 'admin.customers.index'],
                    ['Discounts', 'admin.discounts.index'],
                    ['Pages', 'admin.pages.index'],
                    ['Navigation', 'admin.navigation.index'],
                    ['Themes', 'admin.themes.index'],
                    ['Analytics', 'admin.analytics.index'],
                    ['Settings', 'admin.settings.index'],
                    ['Apps', 'admin.apps.index'],
                    ['Developers', 'admin.developers.index'],
                ] as [$label, $route])
                    <a href="{{ route($route) }}" class="rounded-md px-3 py-2 {{ request()->routeIs($route) ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>
        <div>
            <header class="flex items-center justify-between gap-4 border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Current store</p>
                    <p class="font-medium">{{ app('current_store')->name }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a class="rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700" href="{{ route('home') }}">Storefront</a>
                    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="rounded-md bg-zinc-950 px-3 py-2 text-sm text-white dark:bg-white dark:text-zinc-950">Log out</button></form>
                </div>
            </header>
            <main class="p-4 lg:p-8">
                @if(session('status'))
                    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
