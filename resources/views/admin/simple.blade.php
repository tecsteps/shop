<x-admin.layout :title="ucwords($section)">
    <h1 class="text-3xl font-bold tracking-normal">{{ ucwords($section) }}</h1>
    <p class="mt-2 text-zinc-600 dark:text-zinc-400">This admin section is available and scoped to {{ app('current_store')->name }}.</p>
    <div class="mt-6 grid gap-3">
        @forelse($records as $record)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                {{ $record->title ?? $record->name ?? $record->order_number ?? ('Record #'.$record->id) }}
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">No records yet.</div>
        @endforelse
    </div>
</x-admin.layout>

