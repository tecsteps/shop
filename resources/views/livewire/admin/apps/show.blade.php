<div class="space-y-6">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Apps'), 'href' => route('admin.apps.index')],
        ['label' => $this->installation->app->name],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-4">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="squares-2x2" class="size-6 text-zinc-500 dark:text-zinc-400" />
            </div>
            <div>
                <flux:heading size="xl" level="1">{{ $this->installation->app->name }}</flux:heading>
                <flux:text class="text-sm">
                    {{ __('Installed :time', ['time' => $this->installation->installed_at?->diffForHumans() ?? __('recently')]) }}
                </flux:text>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-admin.status-badge :status="$this->installation->status" />

            <flux:button
                size="sm"
                variant="danger"
                wire:click="uninstallApp"
                wire:confirm="{{ __('Uninstall this app? Its webhook subscriptions will be disabled.') }}"
                data-test="uninstall-app-button"
            >
                {{ __('Uninstall') }}
            </flux:button>
        </div>
    </div>

    <x-admin.card>
        <flux:heading size="lg">{{ __('Granted scopes') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Permissions this app may use to access store data.') }}</flux:text>

        <div class="mt-4 flex flex-wrap gap-2" data-test="granted-scopes">
            @forelse ($this->installation->scopes_json ?? [] as $scope)
                <flux:badge size="sm" color="zinc">{{ $scope }}</flux:badge>
            @empty
                <flux:text>{{ __('No scopes granted.') }}</flux:text>
            @endforelse
        </div>
    </x-admin.card>

    <x-admin.card class="!p-0">
        <div class="p-6 pb-0">
            <flux:heading size="lg">{{ __('Webhook subscriptions') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Events this app receives from your store.') }}</flux:text>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-6 py-2.5">{{ __('Event type') }}</th>
                        <th class="px-4 py-2.5">{{ __('URL') }}</th>
                        <th class="px-4 py-2.5">{{ __('Status') }}</th>
                        <th class="px-6 py-2.5">{{ __('Last delivery') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->installation->webhookSubscriptions as $subscription)
                        <tr wire:key="subscription-{{ $subscription->id }}">
                            <td class="px-6 py-3 font-mono text-zinc-900 dark:text-white">{{ $subscription->event_type }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $subscription->target_url }}</td>
                            <td class="px-4 py-3"><x-admin.status-badge :status="$subscription->status" /></td>
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">
                                @if ($subscription->latestDelivery !== null)
                                    {{ $subscription->latestDelivery->last_attempt_at?->diffForHumans() ?? __('Pending') }}
                                    @if ($subscription->latestDelivery->response_code !== null)
                                        <flux:badge size="sm" :color="$subscription->latestDelivery->response_code < 300 ? 'green' : 'red'">
                                            {{ $subscription->latestDelivery->response_code }}
                                        </flux:badge>
                                    @endif
                                @else
                                    {{ __('Never') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center">
                                <flux:text>{{ __('This app has no webhook subscriptions.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <x-admin.card>
        <flux:heading size="lg">{{ __('Usage') }}</flux:heading>
        <flux:text class="mt-1">{{ __('API access activity for this installation.') }}</flux:text>

        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <dt class="text-xs font-semibold text-zinc-500 uppercase dark:text-zinc-400">{{ __('Active API tokens') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $this->installation->oauthTokens->count() }}</dd>
            </div>
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <dt class="text-xs font-semibold text-zinc-500 uppercase dark:text-zinc-400">{{ __('Last API call') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Never') }}</dd>
            </div>
        </dl>
    </x-admin.card>
</div>
