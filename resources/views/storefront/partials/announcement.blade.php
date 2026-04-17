@if (! empty($announcement))
    <div class="border-b border-zinc-200 bg-zinc-900 text-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 py-2 text-center text-xs font-medium tracking-wide sm:px-6 lg:px-8">
            {{ $announcement }}
        </div>
    </div>
@endif
