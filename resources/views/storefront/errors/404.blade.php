@extends('storefront.layouts.app')

@section('title', 'Page not found')
@section('content')
    <section class="mx-auto flex min-h-[65vh] max-w-2xl flex-col items-center justify-center px-4 py-16 text-center sm:px-6">
        <p class="text-[7rem] font-semibold leading-none tracking-tighter text-stone-100 dark:text-stone-900" aria-hidden="true">404</p>
        <h1 class="-mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">Page not found</h1>
        <p class="mt-3 max-w-md text-sm leading-6 text-stone-600 dark:text-stone-300">The page you’re looking for doesn’t exist or may have moved.</p>
        <form action="/search" method="get" class="mt-7 flex w-full max-w-md gap-2"><label for="error-search" class="sr-only">Search the store</label><input id="error-search" name="q" type="search" placeholder="Search the store" class="storefront-input min-w-0 flex-1"><button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-stone-950 px-5 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:bg-white dark:text-stone-950">Search</button></form>
        <a href="/" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-300 px-5 text-sm font-semibold hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:border-stone-700 dark:hover:bg-stone-900">Go to home page</a>
    </section>
@endsection
