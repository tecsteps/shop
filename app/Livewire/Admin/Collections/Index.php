<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\AdminComponent;
use App\Models\Collection;
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
        Gate::authorize('viewAny', Collection::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteCollection(int $id): void
    {
        $collection = Collection::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id);
        Gate::authorize('delete', $collection);
        $collection->products()->detach();
        $collection->delete();
        $this->toast('Collection deleted.');
    }

    #[Computed]
    public function collections()
    {
        return Collection::query()->where('store_id', $this->currentStore()->getKey())->withCount('products')
            ->when($this->search, fn (Builder $query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest('updated_at')->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.collections.index');
    }
}
