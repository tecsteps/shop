@props(['empty' => 'No records found.', 'colspan' => 1])

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                {{ $head }}
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                {{ $body }}
            </tbody>
        </table>
    </div>
</div>
