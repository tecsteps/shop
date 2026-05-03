<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection as ProductCollection;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.collections.index', [
            'collections' => ProductCollection::query()
                ->withCount('products')
                ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->latest('updated_at')
                ->paginate(10),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Collections',
        ]);
    }
}
