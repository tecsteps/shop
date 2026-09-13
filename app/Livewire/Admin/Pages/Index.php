<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesAdminStore, WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

    public bool $showDeleteModal = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Page::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deletePage(): void
    {
        $page = Page::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->deletingId)
            ->firstOrFail();

        Gate::authorize('delete', $page);

        $page->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->toast('success', 'Page deleted.');
    }

    public function render()
    {
        $query = Page::query()
            ->where('store_id', $this->store()->getKey())
            ->orderByDesc('updated_at');

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        return view('livewire.admin.pages.index', [
            'pages' => $query->paginate(25),
        ])->layout('livewire.admin.layout.app', ['title' => 'Pages']);
    }
}
