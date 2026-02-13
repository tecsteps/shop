<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Collection::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('delete', Collection::class);

        $collection = Collection::findOrFail($id);
        $collection->delete();

        session()->flash('toast', ['type' => 'success', 'message' => __('Collection deleted.')]);
    }

    public function render(): View
    {
        $query = Collection::query()->withCount('products');

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        return view('livewire.admin.collections.index', [
            'collections' => $query->latest()->paginate(15),
        ]);
    }
}
