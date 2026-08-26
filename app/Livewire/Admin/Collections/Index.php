<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use DispatchesToasts, WithPagination;

    #[Layout('layouts.admin.app')]
    public string $search = '';

    public string $statusFilter = 'all';

    public bool $confirmingDeleteId = false;

    public ?int $deleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Collection::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function collections(): LengthAwarePaginator
    {
        $query = Collection::query()->withCount('products');

        if (trim($this->search) !== '') {
            $query->where('title', 'like', '%'.trim($this->search).'%');
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->latest('updated_at')->paginate(15);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->confirmingDeleteId = true;
    }

    public function deleteCollection(): void
    {
        $collection = Collection::find($this->deleteId);

        if ($collection) {
            $this->authorize('delete', $collection);
            $collection->delete();
            $this->toast('Collection deleted');
        }

        $this->confirmingDeleteId = false;
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.collections.index');
    }
}
