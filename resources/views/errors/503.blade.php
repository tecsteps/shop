<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __("We'll be back soon")])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center bg-white px-4 text-center antialiased dark:bg-zinc-950">
        <p class="text-sm font-semibold tracking-widest text-zinc-400 uppercase dark:text-zinc-600" aria-hidden="true">503</p>
        <h1 class="mt-4 text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">{{ __("We'll be back soon") }}</h1>
        <p class="mx-auto mt-3 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
            {{ __("We're currently performing maintenance. Please check back shortly.") }}
        </p>
    </body>
</html>
