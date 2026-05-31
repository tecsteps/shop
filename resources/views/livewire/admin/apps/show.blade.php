<div>
    <x-admin.breadcrumbs :items="[
        ['label' => __('Apps'), 'href' => route('admin.apps.index')],
        ['label' => $installation->app?->name ?? __('App')],
    ]" />

    <div class="mb-6 flex items-center gap-3">
        <flux:heading size="xl" level="1">{{ $installation->app?->name ?? __('App') }}</flux:heading>
        <flux:badge :color="$installation->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($installation->status->value) }}</flux:badge>
    </div>

    <div class="space-y-6">
        <x-admin.card title="{{ __('Scopes granted') }}">
            @if (empty($installation->scopes_json))
                <flux:text class="text-sm">{{ __('No scopes granted.') }}</flux:text>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($installation->scopes_json as $scope)
                        <flux:badge size="sm" color="zinc">{{ $scope }}</flux:badge>
                    @endforeach
                </div>
            @endif
        </x-admin.card>

        <x-admin.card title="{{ __('Webhook subscriptions') }}">
            @if ($installation->webhookSubscriptions->isEmpty())
                <flux:text class="text-sm">{{ __('No webhook subscriptions.') }}</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Event type') }}</flux:table.column>
                        <flux:table.column>{{ __('URL') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($installation->webhookSubscriptions as $sub)
                            <flux:table.row :key="'sub-'.$sub->id">
                                <flux:table.cell>{{ $sub->event_type }}</flux:table.cell>
                                <flux:table.cell class="truncate">{{ $sub->target_url }}</flux:table.cell>
                                <flux:table.cell><flux:badge size="sm" :color="$sub->status->value === 'active' ? 'green' : 'zinc'">{{ ucfirst($sub->status->value) }}</flux:badge></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </x-admin.card>

        <x-admin.card title="{{ __('Installation') }}">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Installed') }}</dt><dd>{{ $installation->installed_at?->format('M j, Y g:i A') }}</dd></div>
            </dl>
            <div class="mt-4">
                <flux:button variant="danger" wire:click="uninstall" wire:confirm="{{ __('Uninstall this app?') }}">{{ __('Uninstall') }}</flux:button>
            </div>
        </x-admin.card>
    </div>
</div>
