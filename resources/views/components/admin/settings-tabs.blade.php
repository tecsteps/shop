@props(['active' => 'general'])

@php
    $tabs = [
        'general' => ['label' => __('General'), 'href' => route('admin.settings.index')],
        'domains' => ['label' => __('Domains'), 'href' => route('admin.settings.index', ['tab' => 'domains'])],
        'shipping' => ['label' => __('Shipping'), 'href' => route('admin.settings.shipping')],
        'taxes' => ['label' => __('Taxes'), 'href' => route('admin.settings.taxes')],
        'checkout' => ['label' => __('Checkout'), 'href' => route('admin.settings.index', ['tab' => 'checkout'])],
        'notifications' => ['label' => __('Notifications'), 'href' => route('admin.settings.index', ['tab' => 'notifications'])],
    ];
@endphp

{{-- Settings tab bar (spec 02: tabs General, Domains, Shipping, Taxes, Checkout, Notifications). --}}
<div class="flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700" role="tablist">
    @foreach ($tabs as $key => $tab)
        <a
            href="{{ $tab['href'] }}"
            wire:navigate
            role="tab"
            aria-selected="{{ $active === $key ? 'true' : 'false' }}"
            data-test="settings-tab-{{ $key }}"
            class="-mb-px border-b-2 px-4 py-2 text-sm whitespace-nowrap transition {{ $active === $key
                ? 'border-blue-600 font-semibold text-zinc-900 dark:border-blue-400 dark:text-white'
                : 'border-transparent font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
