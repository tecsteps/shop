<?php

namespace App\Livewire\Admin\Discounts;

use App\Livewire\Admin\AdminComponent;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->authorizeAction('viewAny', Discount::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteDiscount(int $id): void
    {
        $discount = Discount::query()->findOrFail($id);
        $this->authorizeAction('delete', $discount);
        $discount->delete();
        $this->toast('Discount deleted.');
    }

    #[Computed]
    public function discounts(): LengthAwarePaginator
    {
        return Discount::query()
            ->when($this->search !== '', fn (Builder $query) => $query->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter === 'scheduled', fn (Builder $query) => $query->where('starts_at', '>', now())->where('status', 'active'))
            ->when($this->statusFilter === 'expired', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->where('status', 'expired')->orWhere('ends_at', '<', now())))
            ->when($this->statusFilter === 'active', fn (Builder $query) => $query->where('status', 'active')->where('starts_at', '<=', now())->where(fn (Builder $nested) => $nested->whereNull('ends_at')->orWhere('ends_at', '>=', now())))
            ->latest('created_at')->paginate(20);
    }

    public function render(): View
    {
        return $this->admin(view('admin.discounts.index'), 'Discounts', [['label' => 'Discounts']]);
    }
}
