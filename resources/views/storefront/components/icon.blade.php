@php($class = $class ?? 'size-5')
<svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name ?? '')
        @case('bag')
            <path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/><path d="M9 12v1m6-1v1"/>
            @break
        @case('search')
            <circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.4 4.4"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16"/>
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18"/>
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6"/>
            @break
        @case('chevron-left')
            <path d="m15 18-6-6 6-6"/>
            @break
        @case('chevron-right')
            <path d="m9 18 6-6-6-6"/>
            @break
        @case('arrow-right')
            <path d="M4 12h15m-6-6 6 6-6 6"/>
            @break
        @case('check')
            <path d="m5 12 4 4L19 6"/>
            @break
        @case('minus')
            <path d="M5 12h14"/>
            @break
        @case('plus')
            <path d="M12 5v14m-7-7h14"/>
            @break
        @case('pin')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
            @break
        @case('package')
            <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9M7.5 5.5l9 5"/>
            @break
        @case('truck')
            <path d="M3 6h11v12H3zM14 10h4l3 3v5h-7z"/><circle cx="7.5" cy="19" r="1.5"/><circle cx="17.5" cy="19" r="1.5"/>
            @break
        @case('alert')
            <path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v5m0 3h.01"/>
            @break
        @case('heart')
            <path d="M20.8 8.5c0 5.5-8.8 11-8.8 11s-8.8-5.5-8.8-11A4.5 4.5 0 0 1 12 6a4.5 4.5 0 0 1 8.8 2.5Z"/>
            @break
        @default
            <circle cx="12" cy="12" r="8"/>
    @endswitch
</svg>
