@props(['active' => 'general'])

{{--
    Settings tab bar. General/Domains/Checkout/Notifications are tabs on the
    settings index (switched via the `tab` Livewire property); Shipping and
    Taxes are dedicated routes. `active` is the current tab/route key.
--}}
@php
    $inlineTabs = ['general' => __('General'), 'domains' => __('Domains'), 'checkout' => __('Checkout'), 'notifications' => __('Notifications')];
@endphp

<div class="mb-6 flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist" aria-label="{{ __('Settings sections') }}">
    @foreach ($inlineTabs as $key => $label)
        @if (request()->routeIs('admin.settings.index'))
            <button
                type="button"
                wire:click="$set('tab', '{{ $key }}')"
                @class([
                    'border-b-2 px-4 py-2 text-sm transition',
                    'border-zinc-900 font-semibold text-zinc-900 dark:border-white dark:text-white' => $active === $key,
                    'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $active !== $key,
                ])
                data-test="settings-tab-{{ $key }}"
            >{{ $label }}</button>
        @else
            <a
                href="{{ route('admin.settings.index') }}"
                wire:navigate
                class="border-b-2 border-transparent px-4 py-2 text-sm text-zinc-500 transition hover:text-zinc-800 dark:hover:text-zinc-200"
            >{{ $label }}</a>
        @endif
    @endforeach

    <a href="{{ route('admin.settings.shipping') }}" wire:navigate
       @class([
           'border-b-2 px-4 py-2 text-sm transition',
           'border-zinc-900 font-semibold text-zinc-900 dark:border-white dark:text-white' => $active === 'shipping',
           'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $active !== 'shipping',
       ])>{{ __('Shipping') }}</a>

    <a href="{{ route('admin.settings.taxes') }}" wire:navigate
       @class([
           'border-b-2 px-4 py-2 text-sm transition',
           'border-zinc-900 font-semibold text-zinc-900 dark:border-white dark:text-white' => $active === 'taxes',
           'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $active !== 'taxes',
       ])>{{ __('Taxes') }}</a>
</div>
