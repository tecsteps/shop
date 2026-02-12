<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
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

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $typeFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $store = app('current_store');

        Collection::query()
            ->where('store_id', $store->id)
            ->where('id', $id)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Collection deleted.');
    }

    public function render()
    {
        $store = app('current_store');

        $collections = Collection::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->withCount('products')
            ->latest()
            ->paginate(15);

        return view('livewire.admin.collections.index', [
            'collections' => $collections,
            'statuses' => CollectionStatus::cases(),
            'types' => CollectionType::cases(),
        ]);
    }
}
