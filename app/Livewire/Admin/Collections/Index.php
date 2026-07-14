<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\AdminComponent;
use App\Models\Collection;
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
        $this->authorizeAction('viewAny', Collection::class);
    }

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
        $this->authorizeAction('delete', $collection);
        $collection->delete();
        $this->toast('Collection deleted.');
    }

    #[Computed]
    public function collections(): LengthAwarePaginator
    {
        return Collection::query()->withCount('products')
            ->when($this->search !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest('updated_at')->paginate(20);
    }

    public function render(): View
    {
        return $this->admin(view('admin.collections.index'), 'Collections', [['label' => 'Collections']]);
    }
}
