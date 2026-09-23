<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Customize '.$theme->name.' · '.config('app.name')])
    <style>[x-cloak] { display: none !important; }</style>
    <script>
        (() => {
            const preference = localStorage.getItem('theme');
            const dark = preference ? preference === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    {{ $slot }}

    @fluxScripts
</body>
</html>
