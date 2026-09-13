<?php

namespace App\Livewire\Admin\Discounts;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Discount;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesAdminStore, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $deletingId = null;

    public bool $showDeleteModal = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Discount::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteDiscount(): void
    {
        $discount = Discount::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->deletingId)
            ->firstOrFail();

        Gate::authorize('delete', $discount);

        $discount->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->toast('success', 'Discount deleted.');
    }

    public function render()
    {
        $query = Discount::query()
            ->where('store_id', $this->store()->getKey())
            ->orderByDesc('updated_at');

        if ($this->search !== '') {
            $query->where('code', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter === 'scheduled') {
            $query->where('starts_at', '>', now());
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.discounts.index', [
            'discounts' => $query->paginate(25),
        ])->layout('livewire.admin.layout.app', ['title' => 'Discounts']);
    }
}
