@props(['variant' => 'default'])
<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium', 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200' => $variant === 'sale', 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => $variant !== 'sale']) }}>{{ $slot }}</span>
