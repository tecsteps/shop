<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function delete(int $collectionId): void
    {
        $collection = Collection::query()->findOrFail($collectionId);
        $this->authorize('delete', $collection);
        $collection->delete();
        $this->dispatch('toast', message: 'Collection deleted.');
    }

    public function render(): View
    {
        $collections = Collection::query()
            ->withCount('products')
            ->when($this->search !== '', fn ($query) => $query->where(function ($nested): void {
                $nested->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('handle', 'like', '%'.$this->search.'%');
            }))
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.collections.index', compact('collections'))->layout('layouts.admin');
    }
}
