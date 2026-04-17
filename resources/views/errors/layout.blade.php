@php
    /** @var int $status */
    /** @var string $headline */
    /** @var string $message */
    $useStorefront = app()->bound('current_store');
@endphp

@if ($useStorefront)
    <x-layouts.storefront :title="$headline">
        <section class="flex flex-col items-center justify-center gap-5 py-16 text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                Error {{ $status }}
            </p>
            <h1 class="text-3xl font-semibold tracking-tight md:text-4xl">{{ $headline }}</h1>
            <p class="max-w-md text-neutral-600 dark:text-neutral-400">{{ $message }}</p>
            <a href="{{ url('/') }}"
                class="mt-2 inline-flex items-center rounded-full bg-neutral-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 dark:focus-visible:ring-white">
                Return home
            </a>
        </section>
    </x-layouts.storefront>
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $headline }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-white text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
        <main class="flex flex-col items-center gap-4 p-8 text-center" role="main">
            <p class="text-sm font-semibold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                Error {{ $status }}
            </p>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $headline }}</h1>
            <p class="max-w-md text-neutral-600 dark:text-neutral-400">{{ $message }}</p>
            <a href="{{ url('/') }}" class="mt-2 rounded-full bg-neutral-900 px-6 py-3 text-sm font-semibold text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-900">
                Return home
            </a>
        </main>
    </body>
    </html>
@endif
