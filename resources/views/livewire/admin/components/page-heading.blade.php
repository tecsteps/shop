@php($crumbs = $breadcrumbs ?? [])
<div class="mb-7">
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
            <li><a href="/admin" wire:navigate class="rounded-sm hover:text-zinc-950 hover:underline focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:text-white">Home</a></li>
            @foreach ($crumbs as $crumb)
                <li aria-hidden="true"><flux:icon name="chevron-right" class="size-3.5" /></li>
                <li>@if (!empty($crumb['href']) && !$loop->last)<a href="{{ $crumb['href'] }}" wire:navigate class="rounded-sm hover:text-zinc-950 hover:underline focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:text-white">{{ $crumb['label'] }}</a>@else<span @if ($loop->last) aria-current="page" @endif class="font-medium text-zinc-700 dark:text-zinc-200">{{ $crumb['label'] }}</span>@endif</li>
            @endforeach
        </ol>
    </nav>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0"><h1 class="text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl dark:text-white">{{ $title }}</h1>@if (!empty($description))<p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $description }}</p>@endif</div>
        @if (!empty($actionLabel) && !empty($actionHref))<a href="{{ $actionHref }}" wire:navigate class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><flux:icon name="plus" class="size-4" />{{ $actionLabel }}</a>@endif
    </div>
</div>
