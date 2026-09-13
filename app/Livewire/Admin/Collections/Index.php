<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Collection;
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
        Gate::authorize('viewAny', Collection::class);
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

    public function deleteCollection(): void
    {
        $collection = Collection::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->deletingId)
            ->firstOrFail();

        Gate::authorize('delete', $collection);

        $collection->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->toast('success', 'Collection deleted.');
    }

    public function render()
    {
        $query = Collection::query()
            ->where('store_id', $this->store()->getKey())
            ->withCount('products')
            ->orderByDesc('updated_at');

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.collections.index', [
            'collections' => $query->paginate(25),
        ])->layout('livewire.admin.layout.app', ['title' => 'Collections']);
    }
}
