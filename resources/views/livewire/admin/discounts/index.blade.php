<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Discounts') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.discounts.create')" wire:navigate>
            {{ __('Add discount') }}
        </flux:button>
    </div>

    <div class="flex flex-col sm:flex-row gap-4 mb-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by code...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
            <flux:select.option value="expired">{{ __('Expired') }}</flux:select.option>
            <flux:select.option value="disabled">{{ __('Disabled') }}</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="typeFilter" class="w-full sm:w-48">
            <flux:select.option value="all">{{ __('All types') }}</flux:select.option>
            <flux:select.option value="code">{{ __('Code') }}</flux:select.option>
            <flux:select.option value="automatic">{{ __('Automatic') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        @if($this->discounts->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Code') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Type') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Value') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Status') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Usage') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Dates') }}</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50">
                    @foreach($this->discounts as $discount)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="p-3">
                                <a href="{{ route('admin.discounts.edit', $discount) }}" class="text-accent hover:underline" wire:navigate>
                                    {{ $discount->code ?? __('Automatic') }}
                                </a>
                            </td>
                            <td class="p-3">{{ ucfirst($discount->type->value) }}</td>
                            <td class="p-3">
                                @if($discount->value_type->value === 'percent')
                                    {{ $discount->value_amount }}%
                                @elseif($discount->value_type->value === 'fixed')
                                    ${{ number_format($discount->value_amount / 100, 2) }}
                                @else
                                    {{ __('Free shipping') }}
                                @endif
                            </td>
                            <td class="p-3">
                                <flux:badge size="sm" :color="match($discount->effective_status->value) {
                                    'active' => 'green', 'draft' => 'zinc', 'expired' => 'red', 'disabled' => 'yellow',
                                }">{{ ucfirst($discount->effective_status->value) }}</flux:badge>
                            </td>
                            <td class="p-3">{{ $discount->usage_count }}{{ $discount->usage_limit ? ' / '.$discount->usage_limit : '' }}</td>
                            <td class="p-3 text-zinc-500 text-xs">
                                {{ $discount->starts_at?->format('M d') ?? '-' }} - {{ $discount->ends_at?->format('M d') ?? __('No end') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $this->discounts->links() }}</div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="tag" class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No discounts yet') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Create discount codes to offer to your customers.') }}</flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('admin.discounts.create')" wire:navigate>{{ __('Add discount') }}</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
