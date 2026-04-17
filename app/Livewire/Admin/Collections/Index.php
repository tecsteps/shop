<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
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
        Collection::withoutGlobalScopes()->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Collection deleted.');
    }

    #[Computed]
    public function collections(): mixed
    {
        $query = Collection::query()->withCount('products');

        if ($this->search !== '') {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->latest('updated_at')->paginate(15);
    }

    public function render(): mixed
    {
        return view('livewire.admin.collections.index')
            ->layout('layouts.admin', [
                'breadcrumbs' => [['label' => 'Collections']],
            ]);
    }
}
