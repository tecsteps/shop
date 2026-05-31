<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Collection;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Collection list: searchable, status-filterable, paginated, with delete.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Collection::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteCollection(int $id): void
    {
        $collection = Collection::query()->find($id);

        if ($collection === null) {
            return;
        }

        $this->authorize('delete', $collection);
        $collection->delete();

        $this->dispatch('toast', type: 'success', message: __('Collection deleted'));
    }

    public function getCollectionsProperty()
    {
        return Collection::query()
            ->withCount('products')
            ->when($this->search !== '', fn (Builder $q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('updated_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.collections.index');
    }
}
