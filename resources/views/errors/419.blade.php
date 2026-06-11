<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Page expired')])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center bg-white px-4 text-center antialiased dark:bg-zinc-950">
        <p class="text-sm font-semibold tracking-widest text-zinc-400 uppercase dark:text-zinc-600" aria-hidden="true">419</p>
        <h1 class="mt-4 text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">{{ __('Page expired') }}</h1>
        <p class="mx-auto mt-3 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Your session expired. Please go back, refresh the page, and try again.') }}
        </p>
        <a
            href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
            class="mt-8 inline-flex items-center justify-center rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
            {{ __('Go back') }}
        </a>
    </body>
</html>
