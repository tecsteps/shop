<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Collections')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteCollection(int $id): void
    {
        $store = app('current_store');
        Collection::query()
            ->where('store_id', $store->id)
            ->where('id', $id)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Collection deleted.');
    }

    /**
     * @return LengthAwarePaginator<Collection>
     */
    #[Computed]
    public function collections(): LengthAwarePaginator
    {
        $store = app('current_store');

        return Collection::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->withCount('products')
            ->latest('updated_at')
            ->paginate(15);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.collections.index');
    }
}
