<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Collection::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Open the delete confirmation modal for a collection.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    /**
     * Delete the collection and detach its products (spec 03 §5.1).
     */
    public function delete(): void
    {
        $this->confirmingDelete = false;

        $collection = Collection::query()->find($this->deletingId);
        $this->deletingId = null;

        if ($collection === null) {
            return;
        }

        $this->authorize('delete', $collection);

        $collection->products()->detach();
        $collection->delete();

        $this->dispatch('toast', type: 'success', message: 'Collection deleted');
    }

    public function render(): View
    {
        $collections = Collection::query()
            ->withCount('products')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.addcslashes($this->search, '\\%_').'%';

                $query->where('title', 'like', $term);
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('livewire.admin.collections.index', [
            'collections' => $collections,
            'hasCollections' => Collection::query()->exists(),
        ])->layout('admin.layouts.app')->title('Collections');
    }
}
