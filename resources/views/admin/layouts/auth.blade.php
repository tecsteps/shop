<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>
            (() => {
                const stored = localStorage.getItem('theme');
                const dark = stored ? stored === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-950 antialiased dark:bg-slate-950 dark:text-white">
        <main class="grid min-h-screen place-items-center px-4 py-12">
            <div class="w-full max-w-md">
                <a href="{{ url('/admin/login') }}" class="mx-auto mb-8 flex w-fit items-center gap-3 text-xl font-semibold tracking-tight">
                    <span class="grid size-10 place-items-center rounded-xl bg-blue-700 text-white">S</span>
                    {{ config('app.name', 'Shop') }} Admin
                </a>
                {{ $slot }}
            </div>
        </main>
        @fluxScripts
    </body>
</html>
