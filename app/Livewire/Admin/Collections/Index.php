<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteCollection(int $id): void
    {
        $collection = Collection::query()->findOrFail($id);

        $this->authorize('delete', $collection);

        $collection->delete();

        $this->dispatch('toast', type: 'success', message: __('Collection deleted'));
    }

    public function collections(): LengthAwarePaginator
    {
        return Collection::query()
            ->withCount('products')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where('title', 'like', '%'.$this->search.'%');
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest('updated_at')
            ->paginate(15);
    }

    public function render(): mixed
    {
        $this->authorize('viewAny', Collection::class);

        return view('livewire.admin.collections.index', [
            'collections' => $this->collections(),
        ])->layout('layouts.app', [
            'title' => __('Collections'),
        ]);
    }
}
