@extends('storefront.layouts.app')

@section('content')
    <div class="flex min-h-[70vh] flex-col items-center justify-center px-4 py-20 text-center">
        <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
            {{ $storeName }}
        </span>

        <h1 class="mt-6 text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">We will be back soon</h1>
        <p class="mt-3 max-w-md text-zinc-500 dark:text-zinc-400">
            We are currently performing maintenance. Please check back shortly.
        </p>
    </div>
@endsection
