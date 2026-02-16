@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 ' . $class]) }}>
    {{ $slot }}
</div>
