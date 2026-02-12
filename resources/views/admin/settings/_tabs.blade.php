<div class="mb-6 border-b border-zinc-200">
    <nav class="flex flex-wrap gap-2 pb-3 text-sm">
        <a href="{{ route('admin.settings.general') }}" class="rounded-md border px-3 py-1.5 {{ request()->routeIs('admin.settings.general') ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}">
            General
        </a>
        <a href="{{ route('admin.settings.shipping') }}" class="rounded-md border px-3 py-1.5 {{ request()->routeIs('admin.settings.shipping') ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}">
            Shipping
        </a>
        <a href="{{ route('admin.settings.taxes') }}" class="rounded-md border px-3 py-1.5 {{ request()->routeIs('admin.settings.taxes') ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}">
            Taxes
        </a>
    </nav>
</div>
