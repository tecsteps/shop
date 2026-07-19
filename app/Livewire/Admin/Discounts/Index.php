<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Discount::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Disable an active discount (active -> disabled, spec 05 §7).
     */
    public function disable(int $discountId): void
    {
        $discount = Discount::query()->findOrFail($discountId);
        $this->authorize('update', $discount);

        if ($discount->status !== DiscountStatus::Active) {
            $this->dispatch('toast', type: 'error', message: 'Only active discounts can be disabled.');

            return;
        }

        $discount->update(['status' => DiscountStatus::Disabled]);

        $this->dispatch('toast', type: 'success', message: 'Discount disabled');
    }

    /**
     * Re-enable a disabled discount or activate a draft (spec 05 §7).
     */
    public function enable(int $discountId): void
    {
        $discount = Discount::query()->findOrFail($discountId);
        $this->authorize('update', $discount);

        if (! in_array($discount->status, [DiscountStatus::Disabled, DiscountStatus::Draft], true)) {
            $this->dispatch('toast', type: 'error', message: 'Only draft or disabled discounts can be activated.');

            return;
        }

        $discount->update(['status' => DiscountStatus::Active]);

        $this->dispatch('toast', type: 'success', message: 'Discount activated');
    }

    /**
     * Open the delete confirmation modal (spec 03 §19.3).
     */
    public function confirmDelete(int $discountId): void
    {
        $this->deletingId = $discountId;
        $this->confirmingDelete = true;
    }

    /**
     * Delete the discount (owner/admin only per DiscountPolicy).
     */
    public function delete(): void
    {
        $discount = Discount::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $discount);

        $discount->delete();

        $this->confirmingDelete = false;
        $this->deletingId = null;

        $this->dispatch('toast', type: 'success', message: 'Discount deleted');
    }

    /**
     * Effective display status: derives "scheduled" and "expired" from the
     * date window of active discounts (spec 03 §10.1).
     */
    public function displayStatus(Discount $discount): string
    {
        return match (true) {
            $discount->status === DiscountStatus::Active && $discount->starts_at?->isFuture() => 'scheduled',
            $discount->status === DiscountStatus::Active && $discount->ends_at?->isPast() => 'expired',
            default => $discount->status->value,
        };
    }

    /**
     * Human-readable discount value, e.g. "10%", "5.00 EUR", "Free shipping".
     */
    public function displayValue(Discount $discount, string $currency): string
    {
        return match ($discount->value_type) {
            DiscountValueType::Percent => $discount->value_amount.'%',
            DiscountValueType::Fixed => Money::format($discount->value_amount, $currency),
            DiscountValueType::FreeShipping => 'Free shipping',
        };
    }

    public function render(): View
    {
        $discounts = $this->discountsQuery()->paginate(15);

        /** @var \App\Models\Store $store */
        $store = app('current_store');

        return view('livewire.admin.discounts.index', [
            'discounts' => $discounts,
            'hasDiscounts' => Discount::query()->exists(),
            'currency' => $store->default_currency,
        ])->layout('admin.layouts.app')->title('Discounts');
    }

    /**
     * Base query with code search and status/type filters (spec 03 §10.1).
     *
     * @return Builder<Discount>
     */
    private function discountsQuery(): Builder
    {
        return Discount::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.addcslashes($this->search, '\\%_').'%';

                $query->where('code', 'like', $term);
            })
            ->when($this->typeFilter !== 'all', fn (Builder $query) => $query->where('type', $this->typeFilter))
            ->when($this->statusFilter !== 'all', function (Builder $query): void {
                match ($this->statusFilter) {
                    'draft' => $query->where('status', DiscountStatus::Draft),
                    'active' => $query->where('status', DiscountStatus::Active)
                        ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                        ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now())),
                    'scheduled' => $query->where('status', DiscountStatus::Active)
                        ->whereNotNull('starts_at')
                        ->where('starts_at', '>', now()),
                    'expired' => $query->where(fn (Builder $query) => $query
                        ->where('status', DiscountStatus::Expired)
                        ->orWhere(fn (Builder $expired) => $expired
                            ->where('status', DiscountStatus::Active)
                            ->whereNotNull('ends_at')
                            ->where('ends_at', '<=', now()))),
                    'disabled' => $query->where('status', DiscountStatus::Disabled),
                    default => null,
                };
            })
            ->latest('created_at');
    }
}
