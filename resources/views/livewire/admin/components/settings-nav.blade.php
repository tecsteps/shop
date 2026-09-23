<nav aria-label="Store settings" class="mb-6 flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-800">
    @foreach ([['General', '/admin/settings'], ['Shipping', '/admin/settings/shipping'], ['Taxes', '/admin/settings/taxes']] as [$label, $href])
        <a href="{{ $href }}" wire:navigate @if(($active ?? '') === $label) aria-current="page" @endif class="-mb-px inline-flex min-h-11 shrink-0 items-center border-b-2 px-3 text-sm font-medium {{ ($active ?? '') === $label ? 'border-blue-600 text-blue-700 dark:text-blue-300' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}">{{ $label }}</a>
    @endforeach
</nav>
