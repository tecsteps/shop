@extends('errors.layout', ['title' => "We'll be back soon"])

@section('content')
    @php
        // Suspended stores abort with a specific message; real maintenance
        // mode arrives with the generic "Service Unavailable" text.
        $message = $exception->getMessage();
        if ($message === '' || $message === 'Service Unavailable') {
            $message = "We're currently performing maintenance. Please check back shortly.";
        }
    @endphp

    <div class="w-full max-w-lg text-center">
        <svg class="mx-auto size-14 text-gray-300 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384-3.18m0 0a2.4 2.4 0 01-.34-.21c-.31-.22-.47-.56-.47-.93V5.25c0-.37.16-.71.47-.93.09-.06.18-.11.28-.15l6.23-2.67a1.5 1.5 0 011.12 0l6.23 2.67c.1.04.19.09.28.15.31.22.47.56.47.93v5.61c0 .37-.16.71-.47.93-.09.06-.18.11-.28.15l-6.23 2.67a1.5 1.5 0 01-1.12 0l-.72-.31zM12 21v-6" />
        </svg>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">We'll be back soon</h1>
        <p class="mx-auto mt-3 max-w-md text-sm text-gray-500 dark:text-gray-400">
            {{ $message }}
        </p>
    </div>
@endsection
