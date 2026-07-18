<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Page not found</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="relative mx-auto max-w-lg px-4 text-center">
            <p class="pointer-events-none text-[10rem] font-black text-zinc-100 select-none dark:text-zinc-900" aria-hidden="true">404</p>
            <div class="-mt-20">
                <h1 class="text-2xl font-bold">We couldn't find that page</h1>
                <p class="mx-auto mt-2 max-w-sm text-zinc-500 dark:text-zinc-400">
                    The page you're looking for doesn't exist or may have been moved.
                </p>

                <form action="/search" method="GET" class="mx-auto mt-6 flex max-w-sm gap-2">
                    <input
                        type="search"
                        name="q"
                        placeholder="Search products..."
                        class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                    />
                    <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        Search
                    </button>
                </form>

                <a href="/" class="mt-6 inline-block rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                    Go to home page
                </a>
            </div>
        </div>
    </body>
</html>
