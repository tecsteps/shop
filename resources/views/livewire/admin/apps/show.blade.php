<div>
    @include('livewire.admin.components.page-heading', ['title' => $installation->name, 'breadcrumbs' => [['label' => 'Apps', 'href' => route('admin.apps')], ['label' => $installation->name]]])

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(18rem,.7fr)]">
        <div class="grid content-start gap-6">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="app-scopes-heading">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="app-scopes-heading" class="font-semibold">Scopes granted</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Permissions available to this installation.</p>
                    </div>
                    <flux:badge color="{{ $installation->status === 'active' ? 'green' : 'zinc' }}">{{ ucfirst($installation->status) }}</flux:badge>
                </div>
                @if (count($grantedScopes))
                    <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                        @foreach ($grantedScopes as $scope)
                            <li wire:key="app-scope-{{ $loop->index }}" class="rounded-xl bg-zinc-50 px-3 py-2.5 font-mono text-xs text-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">{{ $scope }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-5 rounded-xl bg-zinc-50 px-4 py-5 text-sm text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">No scopes are recorded for this app.</p>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="app-webhooks-heading">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <h2 id="app-webhooks-heading" class="font-semibold">Webhook subscriptions</h2>
                </div>
                @if ($subscriptions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[36rem] text-left text-sm">
                            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950/50 dark:text-zinc-400">
                                <tr><th scope="col" class="px-5 py-3">Event</th><th scope="col" class="px-4 py-3">Target URL</th><th scope="col" class="px-5 py-3">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($subscriptions as $subscription)
                                    <tr wire:key="app-webhook-{{ $subscription->id }}">
                                        <th scope="row" class="px-5 py-4 font-mono text-xs font-medium">{{ $subscription->event_type }}</th>
                                        <td class="max-w-80 truncate px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $subscription->target_url }}</td>
                                        <td class="px-5 py-4"><flux:badge color="{{ $subscription->status === 'active' ? 'green' : 'zinc' }}">{{ ucfirst($subscription->status) }}</flux:badge></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">This app has no webhook subscriptions.</p>
                @endif
            </section>
        </div>

        <aside class="grid content-start gap-6">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="app-usage-heading">
                <h2 id="app-usage-heading" class="font-semibold">Usage</h2>
                <dl class="mt-5 grid gap-4 text-sm">
                    <div><dt class="text-zinc-500 dark:text-zinc-400">API calls</dt><dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($apiCallCount) }}</dd></div>
                    <div><dt class="text-zinc-500 dark:text-zinc-400">Last API call</dt><dd class="mt-1 font-medium">{{ $lastApiCallAt ? \Illuminate\Support\Carbon::parse($lastApiCallAt)->format('M j, Y · g:i A') : 'No API calls recorded' }}</dd></div>
                    <div><dt class="text-zinc-500 dark:text-zinc-400">Installed</dt><dd class="mt-1 font-medium">{{ \Illuminate\Support\Carbon::parse($installation->created_at)->format('M j, Y') }}</dd></div>
                </dl>
            </section>
            <a href="{{ route('admin.apps') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-zinc-300 px-4 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Back to apps</a>
        </aside>
    </div>
</div>
