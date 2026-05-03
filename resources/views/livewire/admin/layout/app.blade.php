<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-50">
        <div class="min-h-screen lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
            <livewire:admin.layout.sidebar />

            <div class="min-w-0">
                <livewire:admin.layout.top-bar />

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl space-y-6">
                        <livewire:admin.layout.breadcrumbs :title="$title ?? 'Admin'" />

                        @if ($toast = session('admin_toast'))
                            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                                {{ $toast['message'] }}
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
