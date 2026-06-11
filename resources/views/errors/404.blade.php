<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Page not found')])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center bg-white px-4 text-center antialiased dark:bg-zinc-950">
        <p class="text-sm font-semibold tracking-widest text-zinc-400 uppercase dark:text-zinc-600" aria-hidden="true">404</p>
        <h1 class="mt-4 text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">{{ __('Page not found') }}</h1>
        <p class="mx-auto mt-3 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
            {{ __("The page you're looking for doesn't exist or has been moved.") }}
        </p>
        <a
            href="{{ url('/') }}"
            class="mt-8 inline-flex items-center justify-center rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
            {{ __('Go to home page') }}
        </a>
    </body>
</html>
