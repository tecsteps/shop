<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Discounts')]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Discounts') }}</flux:heading>

        @can('create', \App\Models\Discount::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.discounts.create')" wire:navigate data-test="create-discount-button">
                {{ __('Create discount') }}
            </flux:button>
        @endcan
    </div>

    @if (! $this->hasAnyDiscounts)
        <x-admin.card class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon name="tag" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('Create your first discount') }}</flux:heading>
            <flux:text>{{ __('Offer discount codes or automatic discounts at checkout.') }}</flux:text>
            @can('create', \App\Models\Discount::class)
                <flux:button variant="primary" :href="route('admin.discounts.create')" wire:navigate class="mt-2">
                    {{ __('Create discount') }}
                </flux:button>
            @endcan
        </x-admin.card>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Search by code...')"
                class="max-w-xs"
                data-test="discount-search"
            />

            <flux:select wire:model.live="statusFilter" size="sm" class="max-w-44">
                <flux:select.option value="all">{{ __('Status: All') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="scheduled">{{ __('Scheduled') }}</flux:select.option>
                <flux:select.option value="expired">{{ __('Expired') }}</flux:select.option>
                <flux:select.option value="disabled">{{ __('Disabled') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="typeFilter" size="sm" class="max-w-44">
                <flux:select.option value="all">{{ __('Type: All') }}</flux:select.option>
                <flux:select.option value="code">{{ __('Code') }}</flux:select.option>
                <flux:select.option value="automatic">{{ __('Automatic') }}</flux:select.option>
            </flux:select>
        </div>

        <x-admin.card class="!p-0">
            <div class="overflow-x-auto" wire:loading.class="opacity-50">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="px-4 py-2.5">{{ __('Code') }}</th>
                            <th class="px-4 py-2.5">{{ __('Type') }}</th>
                            <th class="px-4 py-2.5">{{ __('Value') }}</th>
                            <th class="px-4 py-2.5">{{ __('Usage') }}</th>
                            <th class="px-4 py-2.5">{{ __('Status') }}</th>
                            <th class="px-4 py-2.5">{{ __('Dates') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->discounts as $discount)
                            <tr wire:key="discount-{{ $discount->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $discount->code ?? __('Automatic') }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge size="sm" :color="$discount->type === \App\Enums\DiscountType::Code ? 'blue' : 'purple'">
                                        {{ $discount->type === \App\Enums\DiscountType::Code ? __('Code') : __('Automatic') }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    @switch($discount->value_type)
                                        @case(\App\Enums\DiscountValueType::Percent)
                                            {{ $discount->value_amount }}%
                                            @break
                                        @case(\App\Enums\DiscountValueType::Fixed)
                                            {{ \App\Support\Storefront\PriceFormatter::format($discount->value_amount, app('current_store')->default_currency) }}
                                            @break
                                        @default
                                            {{ __('Free shipping') }}
                                    @endswitch
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ number_format($discount->usage_count) }} / {{ $discount->usage_limit !== null ? number_format($discount->usage_limit) : __('unlimited') }}
                                </td>
                                <td class="px-4 py-3"><x-admin.status-badge :status="$this->displayStatus($discount)" /></td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $discount->starts_at?->format('M j, Y') }}
                                    @if ($discount->ends_at !== null)
                                        - {{ $discount->ends_at->format('M j, Y') }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center">
                                    <flux:text>{{ __('No discounts match your filters.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->discounts->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->discounts->links() }}
                </div>
            @endif
        </x-admin.card>
    @endif
</div>
