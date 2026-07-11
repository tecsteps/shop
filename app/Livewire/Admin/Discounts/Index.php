<?php

namespace App\Livewire\Admin\Discounts;

use App\Livewire\Admin\AdminComponent;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', Discount::class);
    }

    public function toggleStatus(int $id): void
    {
        $discount = Discount::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id);
        Gate::authorize('update', $discount);
        $discount->update(['status' => $discount->status->value === 'active' ? 'disabled' : 'active']);
        $this->toast('Discount status updated.');
    }

    #[Computed]
    public function discounts()
    {
        return Discount::query()->where('store_id', $this->currentStore()->getKey())
            ->when($this->search, fn (Builder $query) => $query->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest()->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.discounts.index');
    }
}
