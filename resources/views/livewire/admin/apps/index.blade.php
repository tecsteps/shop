<div>
    @include('livewire.admin.components.page-heading', ['title' => 'Apps', 'breadcrumbs' => [['label' => 'Apps']]])

    @if ($installedApps->isEmpty())
        <section class="mx-auto grid max-w-2xl justify-items-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900" aria-labelledby="apps-empty-heading">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"><flux:icon name="squares-2x2" class="size-7" /></span>
            <h2 id="apps-empty-heading" class="mt-5 text-lg font-semibold">No apps installed</h2>
            <p class="mt-2 max-w-md text-sm leading-6 text-zinc-600 dark:text-zinc-300">Apps connected to this store will appear here. The app marketplace and installation API are not available yet.</p>
        </section>
    @else
        <section aria-label="Installed apps" class="grid gap-4 lg:grid-cols-2">
            @foreach ($installedApps as $app)
                <article wire:key="installed-app-{{ $app->id }}" class="flex flex-wrap items-start gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"><flux:icon name="squares-2x2" class="size-6" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><h2 class="text-base font-semibold"><a href="{{ route('admin.apps.show', ['installation' => $app->installation_id]) }}" wire:navigate class="underline decoration-transparent underline-offset-4 hover:decoration-current">{{ $app->name }}</a></h2><flux:badge color="{{ $app->status === 'active' ? 'green' : 'zinc' }}">{{ ucfirst($app->status) }}</flux:badge></div>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Installed {{ \Illuminate\Support\Carbon::parse($app->created_at)->diffForHumans() }}</p>
                        @if ($app->description)
                            <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $app->description }}</p>
                        @endif
                    </div>
                    <flux:button type="button" variant="danger" wire:confirm="Uninstall {{ $app->name }} from this store?" wire:click="uninstallApp({{ $app->id }})">Uninstall</flux:button>
                </article>
            @endforeach
        </section>
    @endif
</div>
