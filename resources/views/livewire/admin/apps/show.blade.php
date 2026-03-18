<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button :href="route('admin.apps.index')" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to Apps') }}
        </flux:button>
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="squares-2x2" class="w-6 h-6 text-zinc-500" />
                </div>
                <div>
                    <flux:heading size="xl">{{ $installation->app->name }}</flux:heading>
                    <flux:badge :color="$installation->status === 'active' ? 'green' : 'zinc'" class="mt-1">
                        {{ ucfirst($installation->status) }}
                    </flux:badge>
                </div>
            </div>

            @if($installation->status === 'active')
                <flux:button variant="danger" wire:click="uninstall" wire:confirm="{{ __('Are you sure you want to uninstall this app?') }}">
                    {{ __('Uninstall') }}
                </flux:button>
            @endif
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">{{ __('Installed') }}</flux:text>
                    <flux:text>{{ $installation->installed_at?->format('M d, Y H:i') ?? __('N/A') }}</flux:text>
                </div>
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">{{ __('Status') }}</flux:text>
                    <flux:text>{{ ucfirst($installation->status) }}</flux:text>
                </div>
            </div>

            @if($installation->scopes_json)
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500 mb-2">{{ __('Permissions') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach($installation->scopes_json as $scope)
                            <flux:badge color="zinc">{{ $scope }}</flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($installation->webhookSubscriptions->isNotEmpty())
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500 mb-2">{{ __('Webhook Subscriptions') }}</flux:text>
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                                    <th class="p-3 text-left font-medium text-zinc-500">{{ __('Topic') }}</th>
                                    <th class="p-3 text-left font-medium text-zinc-500">{{ __('URL') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($installation->webhookSubscriptions as $sub)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                        <td class="p-3">{{ $sub->topic }}</td>
                                        <td class="p-3 text-zinc-500 truncate max-w-xs">{{ $sub->target_url }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
