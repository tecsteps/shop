<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteCollection(int $id): void
    {
        Collection::withoutGlobalScopes()->findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Collection deleted.');
    }

    public function getCollectionsProperty()
    {
        $query = Collection::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->withCount('products');

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderByDesc('updated_at')->paginate(20);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.collections.index', [
            'collections' => $this->collections,
        ]);
    }
}
