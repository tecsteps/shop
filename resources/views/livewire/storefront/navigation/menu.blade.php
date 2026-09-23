<div class="contents">
@if ($presentation === 'mobile')
    <div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="lg:hidden">
        <button type="button" x-on:click="open = true" x-bind:aria-expanded="open.toString()" aria-controls="storefront-mobile-navigation" aria-label="Open navigation menu" class="flex size-11 items-center justify-center rounded-full text-stone-800 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:text-stone-100 dark:hover:bg-stone-800">
            @include('storefront.components.icon', ['name' => 'menu', 'class' => 'size-5'])
        </button>
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50" role="presentation">
            <button type="button" x-on:click="open = false" class="absolute inset-0 bg-stone-950/50" aria-label="Close navigation menu"></button>
            <nav id="storefront-mobile-navigation" x-trap.noscroll="open" x-on:keydown.escape.window="open = false" role="dialog" aria-modal="true" aria-label="Mobile navigation" class="absolute inset-y-0 left-0 flex w-[min(23rem,88vw)] flex-col bg-white shadow-2xl dark:bg-stone-950">
                <div class="flex min-h-16 items-center justify-between gap-4 border-b border-stone-200 px-5 dark:border-stone-800"><span class="truncate text-lg font-semibold">{{ data_get(app('current_store'), 'name', 'Store') }}</span><button type="button" x-on:click="open = false" autofocus class="inline-flex size-11 items-center justify-center rounded-full hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-900" aria-label="Close navigation menu">@include('storefront.components.icon', ['name' => 'close', 'class' => 'size-5'])</button></div>
                <ul class="flex-1 space-y-1 overflow-y-auto p-4">
                    @forelse ($items as $item)
                        @php($itemLabel = data_get($item, 'label', data_get($item, 'title', 'Browse')))
                        <li>
                            @if (!empty(data_get($item, 'children', [])))
                                <details class="group/nav">
                                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between rounded-xl px-3 text-sm font-medium hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-900 [&::-webkit-details-marker]:hidden">{{ $itemLabel }}@include('storefront.components.icon', ['name' => 'chevron-down', 'class' => 'size-4 transition group-open/nav:rotate-180'])</summary>
                                    <ul class="ml-4 border-l border-stone-200 py-1 pl-3 dark:border-stone-800"><li><a href="{{ data_get($item, 'url', '#') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-900">View all {{ $itemLabel }}</a></li>@foreach (data_get($item, 'children', []) as $child)<li><a href="{{ data_get($child, 'url', '#') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-900">{{ data_get($child, 'label', data_get($child, 'title', 'View')) }}</a></li>@endforeach</ul>
                                </details>
                            @else
                                <a href="{{ data_get($item, 'url', '#') }}" class="flex min-h-12 items-center rounded-xl px-3 text-sm font-medium hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-900">{{ $itemLabel }}</a>
                            @endif
                        </li>
                    @empty
                        <li><a href="/collections" class="flex min-h-12 items-center rounded-xl px-3 text-sm font-medium hover:bg-stone-100 dark:hover:bg-stone-900">Shop all</a></li>
                    @endforelse
                </ul>
                <a href="/account" class="flex min-h-14 items-center gap-3 border-t border-stone-200 px-5 text-sm font-semibold hover:bg-stone-50 dark:border-stone-800 dark:hover:bg-stone-900">@include('storefront.components.icon', ['name' => 'user', 'class' => 'size-5']) My account</a>
            </nav>
        </div>
    </div>
@elseif ($presentation === 'desktop')
    <nav aria-label="Main navigation" class="hidden flex-1 items-center justify-center gap-1 lg:flex">
        @forelse ($items as $item)
            @php($itemChildren = data_get($item, 'children', []))
            <div class="group relative">
                <a href="{{ data_get($item, 'url', '#') }}" class="inline-flex min-h-11 items-center gap-1 rounded-full px-4 text-sm font-medium text-stone-700 hover:bg-stone-100 hover:text-stone-950 focus-visible:outline-2 focus-visible:outline-blue-700 dark:text-stone-200 dark:hover:bg-stone-800 dark:hover:text-white">
                    {{ data_get($item, 'label', data_get($item, 'title', 'Browse')) }}
                    @if (!empty($itemChildren))
                        @include('storefront.components.icon', ['name' => 'chevron-down', 'class' => 'size-3.5'])
                    @endif
                </a>
                @if (!empty($itemChildren))
                    <ul class="invisible absolute left-0 top-full z-50 min-w-52 translate-y-2 rounded-2xl border border-stone-200 bg-white p-2 opacity-0 shadow-xl transition group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 dark:border-stone-700 dark:bg-stone-900">
                        @foreach ($itemChildren as $child)
                            <li><a href="{{ data_get($child, 'url', '#') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">{{ data_get($child, 'label', data_get($child, 'title', 'View')) }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <a href="/collections" class="inline-flex min-h-11 items-center rounded-full px-4 text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">Shop all</a>
        @endforelse
    </nav>
@else
    <div class="contents">
        @forelse (array_slice($columns, 0, 3) as $column)
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-950 dark:text-white">{{ data_get($column, 'label', 'Explore') }}</h2>
                <ul class="mt-4 grid gap-3">
                    @foreach (data_get($column, 'children', []) as $link)
                        <li><a href="{{ data_get($link, 'url', '#') }}" class="text-sm text-stone-600 underline-offset-4 hover:text-stone-950 hover:underline dark:text-stone-300 dark:hover:text-white">{{ data_get($link, 'label', data_get($link, 'title', 'Learn more')) }}</a></li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em]">Explore</h2>
                <ul class="mt-4 grid gap-3 text-sm text-stone-600 dark:text-stone-300"><li><a href="/collections">Shop all</a></li><li><a href="/pages/about">About us</a></li><li><a href="/account">My account</a></li></ul>
            </div>
        @endforelse
    </div>
@endif
</div>
