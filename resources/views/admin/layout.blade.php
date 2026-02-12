<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => trim($__env->yieldContent('title')) !== ''
                ? trim($__env->yieldContent('title')).' · Admin'
                : 'Admin',
        ])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Admin</p>
                    <p class="text-lg font-semibold text-zinc-900">{{ app('current_store')->name ?? 'Store' }}</p>
                </div>

                <div class="flex items-center gap-3 text-sm text-zinc-600">
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                            Log out
                        </button>
                    </form>
                </div>
            </div>

            <nav class="mx-auto w-full max-w-7xl px-4 pb-4 sm:px-6 lg:px-8">
                <ul class="flex flex-wrap gap-2 text-sm">
                    @php
                        $nav = [
                            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
                            ['label' => 'Products', 'route' => 'admin.products.index', 'match' => 'admin.products.*'],
                            ['label' => 'Collections', 'route' => 'admin.collections.index', 'match' => 'admin.collections.*'],
                            ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'match' => 'admin.inventory.*'],
                            ['label' => 'Orders', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*'],
                            ['label' => 'Customers', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*'],
                            ['label' => 'Discounts', 'route' => 'admin.discounts.index', 'match' => 'admin.discounts.*'],
                            ['label' => 'Pages', 'route' => 'admin.pages.index', 'match' => 'admin.pages.*'],
                            ['label' => 'Navigation', 'route' => 'admin.navigation.index', 'match' => 'admin.navigation.*'],
                            ['label' => 'Themes', 'route' => 'admin.themes.index', 'match' => 'admin.themes.*'],
                            ['label' => 'Search', 'route' => 'admin.search.settings.edit', 'match' => 'admin.search.*'],
                            ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'match' => 'admin.analytics.*'],
                            ['label' => 'Apps', 'route' => 'admin.apps.index', 'match' => 'admin.apps.*'],
                            ['label' => 'Developers', 'route' => 'admin.developers.index', 'match' => 'admin.developers.*'],
                            ['label' => 'Settings', 'route' => 'admin.settings.general', 'match' => 'admin.settings.*'],
                        ];
                    @endphp

                    @foreach ($nav as $item)
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="inline-flex items-center rounded-md border px-3 py-1.5 {{ request()->routeIs($item['match']) ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </body>
</html>
