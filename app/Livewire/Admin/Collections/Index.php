<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Collection;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public ?int $deletingCollectionId = null;

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

    public function confirmDelete(int $collectionId): void
    {
        $this->deletingCollectionId = $collectionId;

        Flux::modal('confirm-delete-collection')->show();
    }

    public function deleteCollection(): void
    {
        $collection = Collection::query()->findOrFail($this->deletingCollectionId);

        $this->authorize('delete', $collection);

        $collection->products()->detach();
        $collection->delete();

        Flux::modal('confirm-delete-collection')->close();

        $this->deletingCollectionId = null;
        $this->toast(__('Collection deleted.'));
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Collection>
     */
    #[Computed]
    public function collections(): LengthAwarePaginator
    {
        return Collection::query()
            ->withCount('products')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('updated_at')
            ->paginate(15);
    }

    #[Computed]
    public function hasAnyCollections(): bool
    {
        return Collection::query()->exists();
    }

    public function render(): View
    {
        return view('livewire.admin.collections.index')->title(__('Collections'));
    }
}
