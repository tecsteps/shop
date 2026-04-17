<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public bool $showDeleteModal = false;

    public ?int $deleteId = null;

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
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteCollection(): void
    {
        if (! $this->deleteId) {
            return;
        }

        $collection = Collection::find($this->deleteId);

        if ($collection) {
            $this->authorize('delete', $collection);
            $collection->delete();
            $this->dispatch('toast', type: 'success', message: 'Collection deleted.');
        }

        $this->deleteId = null;
        $this->showDeleteModal = false;
    }

    #[Computed]
    public function collections(): LengthAwarePaginator
    {
        $query = Collection::query()->withCount('products');

        if ($this->search) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderByDesc('updated_at')->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.collections.index')
            ->layout('layouts.admin', ['title' => 'Collections']);
    }
}
